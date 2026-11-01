<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Scopes\OwnerScope;
use App\Services\OwnerExpenseService;
use App\Support\I18n\LocalDate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Expenses (Wave 1 · sub-gate 1) — the owner's view of the CERTIFIED owner-expense backend
 * ({@see OwnerExpenseService}, called by nobody here — this screen is READ-ONLY). Presentation only:
 * no change to the expense/ledger logic, schema, or guarantees. Owner isolation is the
 * {@see OwnerScope} global scope on `owner_expenses` / `owner_ledger` / `owner_statements`, so every
 * read below is already the owner's own rows and a non-linked user fails closed. No route-model-bound
 * props — all reads happen in render() under the active owner+tenant scope.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — the expense history: expense_date, category (the real 'cleaning'/'maintenance'/'other'
 *                enum), description, source (from the polymorphic `source_type`, e.g. a
 *                `maintenance_ticket`), amount, the `deductible` TRI-STATE, the `voided_at` state, the
 *                `flag` explanation (no_owner/no_agreement), and the statement a row landed on
 *                (attributed_ledger_id → owner_ledger.statement_id → owner_statements.period_start —
 *                stamped only at finalize, so an unstamped row honestly says "Not yet on a statement").
 *   • TRI-STATE— `owner_expenses.deductible` is NULLABLE: true / false / NULL ("null until resolved").
 *                Rendered as three distinct states — Deductible / Not deductible / Pending review.
 *                NULL is NEVER coerced to false; the tone reads resolution (confirmed / not-the-owner's
 *                / unresolved), not a value judgement.
 *   • DERIVED  — the "By category" totals + the KPIs ("Spent this year", "Charged to payouts" = Σ
 *                deductible-this-year, "Pending review" = COUNT of NULL-deductible) are Σ of the REAL
 *                rows above, EXCLUDING voided rows (a void is an append-only reversal, so its money no
 *                longer stands — but the row itself is still shown, badged "Voided"). Money totals are
 *                PER CURRENCY and never summed across currencies (no silent FX — invariant #17).
 *   • UNIT     — each row + a filter carry the REAL unit (owner_expenses.unit_id → Unit, name ?? code,
 *                owner-scoped). VENDOR is now REAL (owner_expenses.vendor) — the supplier a cost was paid
 *                to, shown when captured + honestly "—" when unknown; the polymorphic source (maintenance
 *                ticket / housekeeping task) is still shown when present.
 *   • REAL     — Export (CSV) is a real owner-scoped, CAP_EXPORTS-gated download. "Need something
 *                fixed?" is a REAL relay into the owner↔operator message channel (owner.messages.start)
 *                — the same hardened path as My Units, not a fake form.
 *   • REAL (Wave C · GATE 2) — the owner APPROVE / QUERY workflow is now a REAL state machine (owner_expenses
 *                .approval_state pending→approved|queried). A DEDUCTIBLE expense is created PENDING and does NOT
 *                reduce net until APPROVED (the ledger line posts then); QUERY holds it out of net + opens the
 *                relay. The "Needs your approval" section + the "Awaiting your approval" KPI + the attention items
 *                are real; "Charged to payouts" counts only APPROVED. Reconciles #17 in both states (net = Σ
 *                ledger, and a pending expense has no ledger line). `deductible` (agreement-governed) is a
 *                SEPARATE axis: only a deductible expense enters the approval flow; the honest "Pending review"
 *                (deductibility unresolved) list stays as-is below it.
 */
#[Layout('components.layouts.owner')]
class OwnerExpenses extends Component
{
    use FormatsOwnerData;

    /** The REAL `owner_expenses.category` enum — the whole taxonomy, never invented. Labels: {@see categoryLabels()}. */
    private const CATEGORIES = ['cleaning', 'maintenance', 'other'];

    public function render(): View
    {
        /** @var Collection<int, OwnerExpense> $expenses */
        $expenses = OwnerExpense::query()
            ->with('unit')
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();

        $currency = $this->portfolioCurrency($expenses, $expenses);
        $currencies = $this->currencies($expenses);
        $rows = $this->historyRows($expenses, $this->statementLabels($expenses));

        return view('livewire.owner.expenses', [
            'viewer' => $this->viewer(),
            'currency' => $currency,
            'expenses' => $rows,
            // The pending-review rows (real NULL-deductible, non-voided) — surfaced honestly in the wireframe's
            // "needs attention" slot with NO owner action (deductibility is resolved from the agreement, #39).
            'pendingRows' => array_values(array_filter(
                $rows,
                fn (array $r): bool => $r['deductible']['key'] === 'pending' && $r['voided'] === false
            )),
            // Wave C · GATE 2 — the REAL "Needs your approval" rows: pending-approval, non-voided (Approve/Query).
            'approvalRows' => array_values(array_filter(
                $rows,
                fn (array $r): bool => $r['approval'] === 'pending' && $r['voided'] === false
            )),
            'metrics' => $this->metrics($expenses, $currency),
            'byCurrency' => $this->byCurrency($expenses, $currencies),
            'multiCurrency' => count($currencies) > 1,
            'categories' => $this->categoryLabels(),
            'units' => $this->unitFilter($expenses),
        ]);
    }

    /**
     * The REAL expense rows, newest first. Every cell is a stored column — nothing inferred.
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @param  array<string, array{id: string, label: string}>  $statements  keyed by ledger id
     * @return list<array<string, mixed>>
     */
    private function historyRows(Collection $expenses, array $statements): array
    {
        return $expenses->map(function (OwnerExpense $e) use ($statements): array {
            $ledgerId = $e->attributed_ledger_id === null ? null : (string) $e->attributed_ledger_id;

            return [
                'id' => (string) $e->id,
                'date_display' => Carbon::parse($e->expense_date)->translatedFormat(LocalDate::pattern('j M Y')),
                'category' => (string) $e->category,
                'category_label' => $this->categoryLabel((string) $e->category),
                'description' => (string) $e->description,
                // REAL: the supplier/contractor the cost was paid to (owner_expenses.vendor), when captured;
                // honestly "—" when unknown (e.g. a maintenance-sourced cost with no vendor on the ticket).
                'vendor' => filled($e->vendor) ? (string) $e->vendor : null,
                // REAL: the unit this cost belongs to (owner_expenses.unit_id → Unit, owner-scoped). The label
                // reuses the My-Units rule (name ?? code). A manual expense with no unit reads honestly "—".
                'unit' => $this->unitMeta($e),
                'source_label' => $this->sourceLabel($this->sourceType($e)),
                'amount_display' => $this->money((int) $e->amount_minor, (string) $e->currency),
                'deductible' => $this->deductibleMeta($this->deductibleState($e)),
                // Wave C · GATE 2 — the approval state (pending / approved / queried). A pending deductible expense
                // is NOT charged until the owner approves it.
                'approval' => (string) $e->approval_state,
                'flag_note' => $this->flagNote($e->flag === null ? null : (string) $e->flag),
                'voided' => $e->voided_at !== null,
                // Stamped at finalize only — a null here is honestly "not yet on a statement".
                'statement' => $ledgerId === null ? null : ($statements[$ledgerId] ?? null),
            ];
        })->values()->all();
    }

    /**
     * Resolve, for each attributed expense, the statement its ledger line was claimed by:
     * owner_expenses.attributed_ledger_id → owner_ledger.statement_id → owner_statements. Both reads
     * are owner-scoped, so a ledger row belonging to another owner simply does not resolve.
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @return array<string, array{id: string, label: string}>
     */
    private function statementLabels(Collection $expenses): array
    {
        $ledgerIds = $expenses
            ->map(fn (OwnerExpense $e): ?string => $e->attributed_ledger_id === null ? null : (string) $e->attributed_ledger_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ledgerIds === []) {
            return [];
        }

        /** @var Collection<string, string> $statementIdByLedgerId */
        $statementIdByLedgerId = OwnerLedgerEntry::query()
            ->whereIn('id', $ledgerIds)
            ->whereNotNull('statement_id')
            ->pluck('statement_id', 'id');

        if ($statementIdByLedgerId->isEmpty()) {
            return [];
        }

        $statements = OwnerStatement::query()
            ->whereIn('id', $statementIdByLedgerId->values()->unique()->all())
            ->get()
            ->keyBy('id');

        $out = [];

        foreach ($statementIdByLedgerId as $ledgerId => $statementId) {
            $statement = $statements->get((string) $statementId);

            if ($statement instanceof OwnerStatement) {
                $out[(string) $ledgerId] = [
                    'id' => (string) $statement->id,
                    'label' => $statement->period_start->translatedFormat(LocalDate::pattern('F Y')),
                ];
            }
        }

        return $out;
    }

    /**
     * DERIVED headline figures (the wireframe's three KPIs) — Σ of the REAL rows, voided excluded:
     *   • "Spent this year"     = Σ live rows dated in the CURRENT calendar year (display currency).
     *   • "Charged to payouts"  = Σ live DEDUCTIBLE rows this year — the costs that reach the owner ledger.
     *   • "Pending review"      = COUNT of live NULL-deductible rows (currency-free; deductibility unresolved).
     * The money KPIs are display-currency only (never a cross-currency sum, #17). "Awaiting your approval" is
     * REAL (Wave C · #68 — a pending deductible expense the owner approves/queries; `awaiting_count`/`awaiting_display`
     * below); "Pending review" (the NULL-deductible tri-state) is a separate, agreement-governed axis.
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @return array<string, mixed>
     */
    private function metrics(Collection $expenses, string $currency): array
    {
        $year = (int) now()->year;
        $live = $expenses->filter(fn (OwnerExpense $e): bool => $e->voided_at === null);
        $thisYear = $live->filter(
            fn (OwnerExpense $e): bool => (string) $e->currency === $currency && (int) Carbon::parse($e->expense_date)->year === $year
        );
        // ⚠️ CHARGED = only APPROVED deductible (Wave C · GATE 2) — an approved deductible expense is the ONLY one
        // that posts a ledger line + reduces net. A pending/queried one is NOT charged yet (#17 reconciles both).
        $charged = $thisYear->filter(fn (OwnerExpense $e): bool => $this->deductibleState($e) === true && $e->approval_state === OwnerExpense::APPROVAL_APPROVED);

        // AWAITING approval — live pending-approval expenses (deductible, not yet charged). The money total is
        // display-currency only (#17); the count is currency-free.
        $awaiting = $live->filter(fn (OwnerExpense $e): bool => $e->approval_state === OwnerExpense::APPROVAL_PENDING);
        $awaitingThisCurrency = $awaiting->filter(fn (OwnerExpense $e): bool => (string) $e->currency === $currency);

        return [
            'spent_display' => $this->money($this->sumMinor($thisYear), $currency),
            'spent_count' => $thisYear->count(),
            'charged_display' => $this->money($this->sumMinor($charged), $currency),
            'awaiting_count' => $awaiting->count(),
            'awaiting_display' => $this->money($this->sumMinor($awaitingThisCurrency), $currency),
            'pending_count' => $live->filter(fn (OwnerExpense $e): bool => $this->deductibleState($e) === null)->count(),
            'voided_count' => $expenses->count() - $live->count(),
            'year' => $year,
        ];
    }

    /**
     * The unit-filter list — every unit that has at least one of the owner's expenses (owner-scoped by the
     * OwnerScope already applied to the query). A manual expense with no unit is grouped under "Unassigned".
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @return list<array{id: string, name: string}>
     */
    private function unitFilter(Collection $expenses): array
    {
        return $expenses
            ->map(fn (OwnerExpense $e): array => $this->unitMeta($e))
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * A REAL unit label for an expense: name ?? code (the same rule as My Units), owner-scoped via the eager
     * `unit` relation. A manual, unit-less expense reads "Unassigned" with a stable sentinel id (never faked).
     *
     * @return array{id: string, name: string}
     */
    private function unitMeta(OwnerExpense $expense): array
    {
        $unit = $expense->unit;

        if ($unit === null) {
            return ['id' => 'none', 'name' => __('php/livewire/owner/owner_expenses.unassigned')];
        }

        return ['id' => (string) $unit->id, 'name' => (string) ($unit->name ?? $unit->code)];
    }

    /**
     * DERIVED per-category totals, computed PER CURRENCY over the REAL rows with voided rows excluded.
     * Every category of the real enum is listed (a real zero is honest); currencies are never summed
     * together — each renders its own block (invariant #17, no silent FX).
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @param  list<string>  $currencies
     * @return list<array{currency: string, categories: list<array{label: string, total_minor: int, total_display: string, count: int, bar_width: int}>, total_display: string, count: int}>
     */
    private function byCurrency(Collection $expenses, array $currencies): array
    {
        $live = $expenses->filter(fn (OwnerExpense $e): bool => $e->voided_at === null);
        $out = [];

        foreach ($currencies as $currency) {
            $rows = $live->filter(fn (OwnerExpense $e): bool => (string) $e->currency === $currency);
            $blockTotal = $this->sumMinor($rows);

            $categories = [];
            foreach ($this->categoryLabels() as $key => $label) {
                $set = $rows->filter(fn (OwnerExpense $e): bool => (string) $e->category === $key);
                $count = $set->count();
                if ($count === 0) {
                    continue;   // #39 — a category with no real expense doesn't appear (no €0.00 filler row/bar)
                }
                $catTotal = $this->sumMinor($set);
                $categories[] = [
                    'label' => $label,
                    'total_minor' => $catTotal,
                    'total_display' => $this->money($catTotal, $currency),
                    'count' => $count,
                    // Polish Batch 6 — the proportional-bar width: this category's SHARE of the block total
                    // (true proportion; the shown categories reconcile to 100% of the block total, #17). The
                    // same ÷total basis as the statement by-unit split's bar_width (anti-drift), over REAL rows.
                    'bar_width' => $blockTotal > 0 ? (int) round($catTotal / $blockTotal * 100) : 0,
                ];
            }

            $out[] = [
                'currency' => $currency,
                'categories' => $categories,
                'total_display' => $this->money($blockTotal, $currency),
                'count' => $rows->count(),
            ];
        }

        return $out;
    }

    /**
     * Every currency present in the owner's expenses (voided included, so a fully-voided currency still
     * shows its honest zero rather than vanishing).
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @return list<string>
     */
    private function currencies(Collection $expenses): array
    {
        return $expenses
            ->map(fn (OwnerExpense $e): string => (string) $e->currency)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, OwnerExpense>  $expenses
     */
    private function sumMinor(Collection $expenses): int
    {
        return (int) $expenses->sum(fn (OwnerExpense $e): int => (int) $e->amount_minor);
    }

    /**
     * The REAL nullable `deductible` column, normalized WITHOUT ever coercing NULL to false — null is
     * its own state ("not yet resolved"), exactly as the service writes it.
     */
    private function deductibleState(OwnerExpense $expense): ?bool
    {
        $value = $expense->deductible;

        return $value === null ? null : (bool) $value;
    }

    /**
     * The tri-state badge. Tone reads RESOLUTION, not sentiment: resolved-and-yours (paid) /
     * resolved-and-not-yours (draft) / unresolved (pending).
     *
     * @return array{key: string, label: string, tone: string}
     */
    private function deductibleMeta(?bool $deductible): array
    {
        return match ($deductible) {
            true => ['key' => 'yes', 'label' => __('php/livewire/owner/owner_expenses.deductible'), 'tone' => 'paid'],
            false => ['key' => 'no', 'label' => __('php/livewire/owner/owner_expenses.not_deductible'), 'tone' => 'draft'],
            null => ['key' => 'pending', 'label' => __('php/livewire/owner/owner_expenses.pending_review'), 'tone' => 'pending'],
        };
    }

    /**
     * The category enum's labels, in the request's language (translated here, at render time — never in the constant).
     *
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        $labels = [
            'cleaning' => __('php/livewire/owner/owner_expenses.category_cleaning'),
            'maintenance' => __('php/livewire/owner/owner_expenses.category_maintenance'),
            'other' => __('php/livewire/owner/owner_expenses.category_other'),
        ];

        return array_intersect_key($labels, array_flip(self::CATEGORIES));
    }

    private function categoryLabel(string $category): string
    {
        return $this->categoryLabels()[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * The morph alias stored in `owner_expenses.source_type`, read through the Eloquent accessor: the
     * column is created by the migration's `nullableUlidMorphs('source')`, which the static schema
     * scanner does not expand into its two columns. Reading the column (rather than the `source()`
     * relation) is deliberate — this screen labels only WHAT the source is and never loads or renders
     * any attribute of the source record.
     */
    private function sourceType(OwnerExpense $expense): ?string
    {
        $value = $expense->getAttribute('source_type');

        return $value === null ? null : (string) $value;
    }

    /**
     * The record this expense came from, from the REAL morph alias in `source_type`. A manual expense
     * has no source → null (rendered as nothing, never an invented origin). No attribute of the source
     * record is rendered — only what it IS.
     */
    private function sourceLabel(?string $sourceType): ?string
    {
        if ($sourceType === null) {
            return null;
        }

        return match ($sourceType) {
            'maintenance_ticket' => __('php/livewire/owner/owner_expenses.source_maintenance_ticket'),
            'housekeeping_task' => __('php/livewire/owner/owner_expenses.source_housekeeping_task'),
            'unit' => __('php/livewire/owner/owner_expenses.source_unit'),
            default => ucfirst(str_replace('_', ' ', $sourceType)),
        };
    }

    /**
     * The REAL `flag` column — why the service could not attribute the expense. It is the honest reason
     * a row sits at "Pending review", surfaced rather than swallowed.
     */
    private function flagNote(?string $flag): ?string
    {
        if ($flag === null) {
            return null;
        }

        return match ($flag) {
            'no_agreement' => __('php/livewire/owner/owner_expenses.flag_no_agreement'),
            'no_owner' => __('php/livewire/owner/owner_expenses.flag_no_owner'),
            default => ucfirst(str_replace('_', ' ', $flag)),
        };
    }
}
