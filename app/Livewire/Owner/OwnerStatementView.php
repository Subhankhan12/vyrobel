<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Services\OwnerPortalService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Owner\OwnerLedgerAttribution;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Statement (design pass · Screen 1) — a single owner statement + line breakdown, styled from
 * the catalogued "Owner Statement" design and wired to LIVE, owner-scoped data. Presentation only:
 * this reads through {@see OwnerStatementService}'s already-finalized projection and the read-only
 * {@see OwnerPortalService}; it changes NO backend service, schema, or owner-accounting guarantee.
 *
 * The statement id is a plain string prop; the model is resolved in render() via an owner+tenant
 * scoped findOrFail (NOT a typed route-model-bound prop, which Livewire would resolve during
 * SubstituteBindings, BEFORE the tenant/owner middleware). So another OWNER's statement — like
 * another org's — 404s rather than leaking.
 *
 * WIRED REAL: net payable (hero) · the breakdown lines (the real owner_statement_lines, which sum to
 * the cent) · statement history + net total · period navigation · matched payout (amount + status) ·
 * the three owner-scoped PDF exports (statement · booking summary · remittance advice — the last only
 * when a payout is matched), each export-gated (CAP_EXPORTS) and reconciling to these figures.
 * DEFERRED (visually stubbed, honestly labelled — no fake data): by-unit split, occupancy chart, and
 * the history table's CSV export (no CSV builder yet).
 * There is NO invented "payment / card-processing fee" line — the breakdown only ever shows the real
 * components the engine computes.
 */
#[Layout('components.layouts.owner')]
class OwnerStatementView extends Component
{
    use FormatsOwnerData;

    public string $statementId;

    public function mount(string $statement): void
    {
        $this->statementId = $statement;
    }

    public function render(): View
    {
        // Owner + tenant scoped: a cross-owner (or cross-tenant) id resolves to nothing → 404.
        $statement = OwnerStatement::query()->findOrFail($this->statementId);

        $portal = app(OwnerPortalService::class);
        $allStatements = $portal->statements();          // owner-scoped, most recent first
        $symbol = $this->currencySymbol($statement->currency);

        return view('livewire.owner.statement', [
            'statement' => $this->statementPayload($statement, $symbol),
            'lines' => $this->linePayload($statement),
            'history' => $this->historyPayload($allStatements, $statement->id),
            'years' => $this->years($allStatements),
            'totals' => $this->totals($allStatements),
            'nav' => $this->navigation($allStatements, $statement),
            'payout' => $this->payoutPayload($portal->payouts(), $statement, $symbol),
            'unitsCount' => $portal->units()->count(),
            'byUnit' => $this->byUnitPayload($statement),
            'viewer' => $this->viewer(),
        ]);
    }

    /**
     * The REAL per-unit split of this statement (Batch B · GATE 1) — pure ledger arithmetic, so it
     * reconciles to the statement net BY CONSTRUCTION (invariant #17): the split reads the exact ledger
     * lines this statement CLAIMED (`owner_ledger.statement_id`, stamped at finalize), groups them by
     * `unit_id`, and nets each group through the shared {@see OwnerLedgerAttribution} primitive — so
     * Σ(per-unit net) + (unattributed net) == Σ signedMinor == the statement's net_source, to the cent.
     * Per-unit gross = the unit's `room_revenue` credits; occupancy/nights come from the SHARED
     * {@see PerformanceMetrics} over the statement's own period (owner-scoped — the owner's own units only,
     * #28). A DRAFT has no stamped lines yet → an honest "available once finalized" state, never a fake split.
     *
     * Everything is in the ledger's SOURCE currency and reconciles to `net_source_minor`; for a same-currency
     * statement (the common case) that equals the payout-currency net the hero shows.
     *
     * @return array<string, mixed>
     */
    private function byUnitPayload(OwnerStatement $s): array
    {
        $currency = (string) $s->source_currency;
        $symbol = $this->currencySymbol($currency);

        // The exact lines this statement claims (stamped at finalize). Owner-scoped by the global scope.
        $entries = OwnerLedgerEntry::query()
            ->where('statement_id', $s->id)
            ->with('unit')
            ->get();

        if ($entries->isEmpty()) {
            // A draft (nothing stamped yet) — the by-unit split is a property of the sealed statement.
            return ['available' => false, 'units' => [], 'unattributed' => null, 'total_net_display' => null];
        }

        $totalNet = (int) $entries->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());

        // Half-open [period_start, period_end + 1 day) so the last day of the period is included.
        $start = CarbonImmutable::parse($s->period_start->toDateString());
        $end = CarbonImmutable::parse($s->period_end->toDateString())->addDay();

        $units = [];
        $unattributed = null;

        foreach ($entries->groupBy(fn (OwnerLedgerEntry $e): string => (string) ($e->unit_id ?? '')) as $unitId => $rows) {
            // Net via the SHARED primitive (single currency here → one entry keyed by $currency).
            $net = OwnerLedgerAttribution::netPerCurrency($rows)[$currency] ?? 0;
            $pct = $totalNet !== 0 ? (int) round($net / $totalNet * 100) : 0;

            if ((string) $unitId === '') {
                // Unit-less lines (e.g. a flat management fee): an honest remainder so the split still
                // reconciles to the whole. Absent whenever every line is unit-attributed (the demo case).
                $unattributed = [
                    'net_minor' => $net,
                    'net_display' => $this->money($net, $currency),
                    'pct' => $pct,
                ];

                continue;
            }

            // Per-unit gross = the unit's room_revenue credits; occupancy/nights from the shared metric layer.
            $gross = (int) $rows->where('entry_type', 'room_revenue')->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
            $metrics = PerformanceMetrics::forOwnerUnits([(string) $unitId], $start, $end, $currency);
            // unit_id is set here (else-branch) and the FK is nullOnDelete, so the unit row exists.
            $unit = $rows->first()->unit;

            $units[] = [
                'name' => (string) ($unit->name ?? $unit->code ?? 'Unit'),
                'nights' => $metrics->roomNightsSold,
                'occupancy_pct' => $metrics->occupancyPct(),
                'net_minor' => $net,
                'net_display' => $this->money($net, $currency),
                'gross_display' => $this->money($gross, $currency),
                'pct_of_net' => $pct,
                'bar_width' => max(0, min(100, $pct)),
            ];
        }

        // Best-earning unit first — the wireframe's order.
        usort($units, fn (array $a, array $b): int => $b['net_minor'] <=> $a['net_minor']);

        return [
            'available' => true,
            'currency_symbol' => $symbol,
            'unit_count' => count($units),
            'period_label' => $s->period_start->format('F Y'),
            'units' => $units,
            'unattributed' => $unattributed,
            'total_net_minor' => $totalNet,
            'total_net_display' => $this->money($totalNet, $currency),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statementPayload(OwnerStatement $s, string $symbol): array
    {
        return [
            'id' => $s->id,
            'sequence_no' => $s->sequence_no,
            'period_start' => $s->period_start->toDateString(),
            'period_end' => $s->period_end->toDateString(),
            'period_label' => $s->period_start->format('F Y'),
            'period_range' => $s->period_start->format('j M').' – '.$s->period_end->format('j M Y'),
            'status' => $s->status,
            'status_meta' => $this->statusMeta((string) $s->status),
            'currency' => $s->currency,
            'currency_symbol' => $symbol,
            'source_currency' => $s->source_currency,
            'net_source_minor' => (int) $s->net_source_minor,
            'net_payable_minor' => (int) $s->net_payable_minor,
            'net_payable_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'fx_rate' => $s->fx_rate,
            'fx_source' => $s->fx_source,
            'has_unverified' => (bool) $s->has_unverified,
        ];
    }

    /**
     * The REAL statement lines. Descriptions are already truthful (set by OwnerStatementService); the
     * component lines sum EXACTLY to the net line, so the breakdown reconciles to the cent. A derived
     * "% of gross" sublabel is shown only for proportional components (commission / OTA), computed from
     * the statement's own real figures — never a stored or invented value.
     *
     * @return list<array<string, mixed>>
     */
    private function linePayload(OwnerStatement $s): array
    {
        $gross = (int) $s->gross_minor;

        return OwnerStatementLine::query()
            ->where('owner_statement_id', $s->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function (OwnerStatementLine $l) use ($gross): array {
                $amount = (int) $l->amount_minor;
                $isNet = $l->line_type === 'net';

                return [
                    'line_type' => $l->line_type,
                    'description' => $l->description,
                    'sublabel' => $this->lineSublabel((string) $l->line_type, $amount, $gross),
                    'amount_minor' => $amount,
                    'amount_display' => $this->money($amount, (string) $l->currency, signed: ! $isNet),
                    'is_negative' => $amount < 0,
                    'is_net' => $isNet,
                    'unverified' => (bool) $l->unverified,
                ];
            })
            ->all();
    }

    /**
     * A truthful "% of gross" sublabel for proportional deductions, derived from the statement's own
     * real gross/commission figures. Returns null for fixed or non-proportional lines.
     */
    private function lineSublabel(string $lineType, int $amount, int $gross): ?string
    {
        if ($gross === 0 || ! in_array($lineType, ['commission', 'ota_cost'], true)) {
            return null;
        }

        $pct = (int) round(abs($amount) / $gross * 100);
        $of = $lineType === 'commission' ? 'gross room revenue' : 'gross revenue';

        return "{$pct}% of {$of}";
    }

    /**
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function historyPayload(Collection $statements, string $currentId): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'id' => $s->id,
            'month_label' => $s->period_start->format('M Y'),
            'period_range' => $s->period_start->format('j M').' – '.$s->period_end->format('j M Y'),
            'year' => (int) $s->period_start->format('Y'),
            'gross_display' => $this->money((int) $s->gross_minor, (string) $s->source_currency),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'status' => $s->status,
            'status_meta' => $this->statusMeta((string) $s->status),
            'is_current' => $s->id === $currentId,
        ])->values()->all();
    }

    /**
     * Distinct statement years, most recent first — drives the year-filter pills over real rows.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<int>
     */
    private function years(Collection $statements): array
    {
        return $statements
            ->map(fn (OwnerStatement $s): int => (int) $s->period_start->format('Y'))
            ->unique()->sortDesc()->values()->all();
    }

    /**
     * @param  Collection<int, OwnerStatement>  $statements
     * @return array{count: int, net_total_display: string}
     */
    private function totals(Collection $statements): array
    {
        $first = $statements->first();
        $currency = $first !== null ? (string) $first->currency : 'EUR';
        $sum = (int) $statements->sum(fn (OwnerStatement $s): int => (int) $s->net_payable_minor);

        return [
            'count' => $statements->count(),
            'net_total_display' => $this->money($sum, $currency),
        ];
    }

    /**
     * Previous (older) / next (newer) statement by period — real navigation to a sibling statement.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return array{prev: array{id: string, label: string}|null, next: array{id: string, label: string}|null}
     */
    private function navigation(Collection $statements, OwnerStatement $current): array
    {
        $chrono = $statements
            ->sortBy(fn (OwnerStatement $s): string => $s->period_start->toDateString())
            ->values();

        $idx = $chrono->search(fn (OwnerStatement $s): bool => $s->id === $current->id);

        $at = function (int $i) use ($chrono): ?array {
            $s = $chrono->get($i);

            return $s instanceof OwnerStatement
                ? ['id' => $s->id, 'label' => $s->period_start->format('F Y')]
                : null;
        };

        return [
            'prev' => is_int($idx) ? $at($idx - 1) : null,
            'next' => is_int($idx) ? $at($idx + 1) : null,
        ];
    }

    /**
     * The payout matched to this statement (owner-scoped). Amount + status are real; there is NO
     * payout-method / IBAN / estimated-date field in the backend, so the design's bank chrome is
     * omitted rather than faked.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, mixed>
     */
    private function payoutPayload(Collection $payouts, OwnerStatement $s, string $symbol): array
    {
        $payout = $payouts->first(fn (Payout $p): bool => $p->owner_statement_id === $s->id);

        if ($payout instanceof Payout) {
            $paidAt = $payout->paid_at;

            return [
                // The matched payout's id — the ONLY consumer is the owner-scoped remittance-advice PDF
                // link on this screen; null below when no payout is matched, so the link is not offered.
                'id' => $payout->id,
                'amount_display' => $this->money((int) $payout->amount_minor, (string) $payout->currency),
                'status_meta' => $this->payoutStatusMeta((string) $payout->status),
                'rail' => $payout->rail === 'manual' ? 'Bank transfer' : (string) $payout->rail,
                'paid_at' => $paidAt !== null ? Carbon::parse($paidAt)->format('j M Y') : null,
            ];
        }

        // No payout row yet: derive an honest "awaiting"/"draft" state from the statement itself.
        return [
            'id' => null,   // no matched payout ⇒ no remittance advice to download yet
            'amount_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'status_meta' => $s->status === 'draft'
                ? ['label' => 'Not yet finalized', 'tone' => 'draft']
                : ['label' => 'Awaiting payout', 'tone' => 'pending'],
            'rail' => null,
            'paid_at' => null,
        ];
    }
}
