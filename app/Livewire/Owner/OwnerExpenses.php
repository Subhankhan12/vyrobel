<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Scopes\OwnerScope;
use App\Services\OwnerExpenseService;
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
 *   • DERIVED  — the "By category" totals and the total/deductible KPIs are Σ of the REAL rows above,
 *                EXCLUDING voided rows (a void is an append-only reversal, so its money no longer
 *                stands — but the row itself is still shown, badged "Voided"). Totals are computed
 *                PER CURRENCY and never summed across currencies (no silent FX — invariant #17).
 *   • NO BACKEND — CSV export and the owner-side repair request have no backend → disabled "Soon",
 *                never a fake download or a form that posts nowhere.
 *   • OMITTED  — the design's owner APPROVE / QUERY workflow ("Needs your approval", "Approve",
 *                "Query", "Approved · charged to next payout", "Query sent · awaiting reply") is NOT
 *                rendered at all — not even disabled. There is no owner-approval state machine, no
 *                approval columns, and no query channel; `deductible` is resolved from the management
 *                agreement by the service, not by the owner. Drawing that UI — even greyed out — would
 *                assert that an expense is *awaiting the owner's decision*, which is a state that does
 *                not exist. A disabled button implies "soon"; this implies "wrong". So it is omitted.
 */
#[Layout('components.layouts.owner')]
class OwnerExpenses extends Component
{
    use FormatsOwnerData;

    /** The REAL `owner_expenses.category` enum — the whole taxonomy, never invented. */
    private const CATEGORIES = [
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'other' => 'Other',
    ];

    public function render(): View
    {
        /** @var Collection<int, OwnerExpense> $expenses */
        $expenses = OwnerExpense::query()
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();

        $currency = $this->portfolioCurrency($expenses, $expenses);
        $currencies = $this->currencies($expenses);

        return view('livewire.owner.expenses', [
            'viewer' => $this->viewer(),
            'currency' => $currency,
            'expenses' => $this->historyRows($expenses, $this->statementLabels($expenses)),
            'metrics' => $this->metrics($expenses, $currency),
            'byCurrency' => $this->byCurrency($expenses, $currencies),
            'multiCurrency' => count($currencies) > 1,
            'categories' => self::CATEGORIES,
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
                'date_display' => Carbon::parse($e->expense_date)->format('j M Y'),
                'category' => (string) $e->category,
                'category_label' => $this->categoryLabel((string) $e->category),
                'description' => (string) $e->description,
                'source_label' => $this->sourceLabel($this->sourceType($e)),
                'amount_display' => $this->money((int) $e->amount_minor, (string) $e->currency),
                'deductible' => $this->deductibleMeta($this->deductibleState($e)),
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
                    'label' => $statement->period_start->format('F Y'),
                ];
            }
        }

        return $out;
    }

    /**
     * DERIVED headline figures — Σ of the REAL rows, voided excluded. The money KPIs are scoped to the
     * display currency (never a cross-currency sum); the pending-review KPI is a COUNT, so it is
     * currency-free and spans the whole portfolio.
     *
     * @param  Collection<int, OwnerExpense>  $expenses
     * @return array<string, mixed>
     */
    private function metrics(Collection $expenses, string $currency): array
    {
        $live = $expenses->filter(fn (OwnerExpense $e): bool => $e->voided_at === null);
        $inCurrency = $live->filter(fn (OwnerExpense $e): bool => (string) $e->currency === $currency);
        $deductible = $inCurrency->filter(fn (OwnerExpense $e): bool => $this->deductibleState($e) === true);

        return [
            'total_display' => $this->money($this->sumMinor($inCurrency), $currency),
            'total_count' => $inCurrency->count(),
            'deductible_display' => $this->money($this->sumMinor($deductible), $currency),
            'pending_count' => $live->filter(fn (OwnerExpense $e): bool => $this->deductibleState($e) === null)->count(),
            'voided_count' => $expenses->count() - $live->count(),
        ];
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
            foreach (self::CATEGORIES as $key => $label) {
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
            true => ['key' => 'yes', 'label' => 'Deductible', 'tone' => 'paid'],
            false => ['key' => 'no', 'label' => 'Not deductible', 'tone' => 'draft'],
            null => ['key' => 'pending', 'label' => 'Pending review', 'tone' => 'pending'],
        };
    }

    private function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category] ?? ucfirst(str_replace('_', ' ', $category));
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
            'maintenance_ticket' => 'Maintenance ticket',
            'housekeeping_task' => 'Housekeeping task',
            'unit' => 'Unit',
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
            'no_agreement' => 'No management agreement covers this date',
            'no_owner' => 'Not attributed to an owner',
            default => ucfirst(str_replace('_', ' ', $flag)),
        };
    }
}
