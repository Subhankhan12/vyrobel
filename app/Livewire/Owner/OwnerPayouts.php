<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Services\OwnerPortalService;
use App\Support\Payouts\RailLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Payouts (design pass · Screen 3) — a NEW owner-scoped page for the certified Payout backend
 * (there was no dedicated route/component before). Styled from the catalogued "Owner Payouts" design,
 * wired to LIVE data via the read-only {@see OwnerPortalService} (called, never modified). Presentation
 * only: NO change to Payout logic, schema, or guarantees. Owner isolation
 * ({@see OwnerScope}) makes every read the owner's own rows; a non-linked user fails
 * closed. No route-model-bound props — all reads happen in render() under the active owner+tenant scope.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — payout history (amount / date / rail=manual→"Bank transfer" / status / linked statement),
 *                paid-this-year total, next/pending amounts, payout currency.
 *   • DERIVED  — paid-this-year = Σ REAL paid payouts (by paid_at year); next-payout breakdown = the
 *                source statement's REAL lines. No fabricated projections.
 *   • LIFECYCLE— rendered with the REAL states ONLY (statement Finalized → payout Pending → Paid, plus
 *                Failed/Reversed). Payout.status ∈ pending/paid/failed/reversed — the design's
 *                "Scheduled"/"On hold" states DO NOT EXIST and are never invented.
 *   • REAL     — the per-payout REMITTANCE ADVICE PDF (Wave 1 · Sub-gate 2): each row links to
 *                `owner.payouts.remittance`, an owner-scoped download off the real payout + its source
 *                statement's real lines. (Was "Receipt · Soon" — no longer deferred.)
 *   • NO BACKEND — on-hold/verification banner, IBAN/payout-account, estimated dates, upcoming-schedule
 *                projections, bulk exports, and the payout-preference controls with no field
 *                (frequency/day/minimum/pause): omitted or disabled "Soon", never a fake action/IBAN/date.
 */
#[Layout('components.layouts.owner')]
class OwnerPayouts extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);
        $payouts = $portal->payouts();          // owner-scoped, most recent first
        $statements = $portal->statements();     // for statement links + next-payout breakdown
        $currency = $this->portfolioCurrency($statements, $payouts);

        /** @var Collection<string, OwnerStatement> $byId */
        $byId = $statements->keyBy('id');
        $focus = $payouts->first(fn (Payout $p): bool => $p->status === 'pending') ?? $payouts->first();

        return view('livewire.owner.payouts', [
            'viewer' => $this->viewer(),
            'currency' => $currency,
            'currencySymbol' => $this->currencySymbol($currency),
            'payouts' => $this->historyRows($payouts, $byId),
            'metrics' => $this->metrics($payouts, $currency),
            'nextPayout' => $this->nextPayoutCard($focus, $byId, $currency),
            'lifecycle' => $this->lifecycle($focus),
            'statuses' => $payouts->map(fn (Payout $p): string => (string) $p->status)->unique()->sort()->values()->all(),
            'years' => $this->distinctYears($payouts),
        ]);
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @param  Collection<string, OwnerStatement>  $byId
     * @return list<array<string, mixed>>
     */
    private function historyRows(Collection $payouts, Collection $byId): array
    {
        return $payouts->map(function (Payout $p) use ($byId): array {
            $statement = $byId->get((string) $p->owner_statement_id);
            $date = $this->payoutDate($p);

            return [
                'id' => $p->id,
                'amount_display' => $this->money((int) $p->amount_minor, (string) $p->currency),
                'rail_label' => $this->railLabel((string) $p->rail),
                'status' => (string) $p->status,
                'status_meta' => $this->payoutStatusMeta((string) $p->status),
                'date_display' => $date['display'],
                'month_label' => $statement instanceof OwnerStatement ? $statement->period_start->format('M Y') : '—',
                'year' => $date['year'],
                'statement_id' => $statement instanceof OwnerStatement ? $statement->id : null,
            ];
        })->values()->all();
    }

    /**
     * Paid-this-year (Σ real paid payouts in the most recent paid year) + next/pending totals. All real.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, mixed>
     */
    private function metrics(Collection $payouts, string $currency): array
    {
        $paid = $payouts->filter(fn (Payout $p): bool => $p->status === 'paid');
        $paidYear = $paid
            ->map(fn (Payout $p): ?int => $this->payoutDate($p)['year'])
            ->filter(fn (?int $y): bool => $y !== null)
            ->max();

        $paidThisYear = $paidYear === null ? 0 : (int) $paid
            ->filter(fn (Payout $p): bool => $this->payoutDate($p)['year'] === $paidYear)
            ->sum(fn (Payout $p): int => (int) $p->amount_minor);

        $pending = $payouts->filter(fn (Payout $p): bool => $p->status === 'pending');
        $nextPayout = $pending->first();

        return [
            'paid_year' => $paidYear,
            'paid_this_year_display' => $this->money($paidThisYear, $currency),
            'paid_count' => $paid->count(),
            'next_payout_display' => $this->money($nextPayout instanceof Payout ? (int) $nextPayout->amount_minor : 0, $currency),
            'pending_total_display' => $this->money((int) $pending->sum(fn (Payout $p): int => (int) $p->amount_minor), $currency),
            'pending_count' => $pending->count(),
        ];
    }

    /**
     * The focus payout (the pending one, else the most recent) + its source-statement breakdown of REAL
     * lines. No IBAN / estimated date — those have no backing field.
     *
     * @param  Collection<string, OwnerStatement>  $byId
     * @return array<string, mixed>|null
     */
    private function nextPayoutCard(?Payout $payout, Collection $byId, string $currency): ?array
    {
        if (! $payout instanceof Payout) {
            return null;
        }

        $statement = $byId->get((string) $payout->owner_statement_id);

        return [
            'amount_minor' => (int) $payout->amount_minor,
            'amount_display' => $this->money((int) $payout->amount_minor, (string) $payout->currency),
            'currency_symbol' => $this->currencySymbol((string) $payout->currency),
            'status_meta' => $this->payoutStatusMeta((string) $payout->status),
            'rail_label' => $this->railLabel((string) $payout->rail),
            'statement_id' => $statement instanceof OwnerStatement ? $statement->id : null,
            'period_label' => $statement instanceof OwnerStatement ? $statement->period_start->format('F Y') : null,
            'breakdown' => $statement instanceof OwnerStatement ? $this->breakdown($statement) : [],
        ];
    }

    /**
     * The source statement's REAL component lines (same discipline as the statement screen — no invented
     * "payment / card-processing fee" line).
     *
     * @return list<array{label: string, amount_display: string, is_negative: bool, is_net: bool}>
     */
    private function breakdown(OwnerStatement $statement): array
    {
        return OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'label' => (string) $l->description,
                'amount_display' => $this->money((int) $l->amount_minor, (string) $l->currency, signed: $l->line_type !== 'net'),
                'is_negative' => (int) $l->amount_minor < 0,
                'is_net' => $l->line_type === 'net',
            ])->values()->all();
    }

    /**
     * The payout lifecycle rendered with REAL states ONLY: a statement is Finalized, its payout is
     * Pending, then Paid (with Failed / Reversed terminal states). Payout.status has no "Scheduled" or
     * "On hold" — those are never invented here.
     *
     * @return array{steps: list<array{label: string, state: string}>, note: string|null}
     */
    private function lifecycle(?Payout $payout): array
    {
        $status = $payout instanceof Payout ? (string) $payout->status : null;

        $stateOf = function (string $step) use ($status): string {
            if ($status === null) {
                return $step === 'Finalized' ? 'active' : 'todo';
            }

            return match ($status) {
                'paid' => 'done',
                'pending' => match ($step) {
                    'Finalized' => 'done', 'Pending' => 'active', default => 'todo'
                },
                'failed' => match ($step) {
                    'Finalized' => 'done', 'Pending' => 'failed', default => 'todo'
                },
                'reversed' => match ($step) {
                    'Paid' => 'reversed', default => 'done'
                },
            };   // status is the payouts enum (pending/paid/failed/reversed) — exhaustive, no default
        };

        $steps = array_map(
            fn (string $label): array => ['label' => $label, 'state' => $stateOf($label)],
            ['Finalized', 'Pending', 'Paid'],
        );

        $note = match ($status) {
            'failed' => 'The last payout failed; it will be retried as a new payout.',
            'reversed' => 'This payout was reversed — the balance carries to your next statement.',
            default => null,
        };

        return ['steps' => $steps, 'note' => $note];
    }

    /**
     * Delegates to the SHARED rail map so this screen and the Remittance PDF that mirrors it can never
     * disagree about how a payout was sent (same discipline as MoneyFormatter — Wave 1 · Sub-gate 2).
     */
    private function railLabel(string $rail): string
    {
        return RailLabel::for($rail);
    }

    /**
     * The display date for a payout: paid_at (paid), failed_at (failed), else created_at; pending shows
     * "Pending".
     *
     * @return array{display: string, year: int|null}
     */
    private function payoutDate(Payout $p): array
    {
        $when = $p->paid_at ?? $p->failed_at ?? ($p->status === 'pending' ? null : $p->created_at);

        if ($when === null) {
            return ['display' => 'Pending', 'year' => null];
        }

        $c = Carbon::parse($when);

        return ['display' => $c->format('j M Y'), 'year' => (int) $c->format('Y')];
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @return list<int>
     */
    private function distinctYears(Collection $payouts): array
    {
        $years = [];
        foreach ($payouts as $p) {
            $year = $this->payoutDate($p)['year'];
            if ($year !== null) {
                $years[$year] = true;
            }
        }
        $keys = array_keys($years);
        rsort($keys);

        return $keys;
    }
}
