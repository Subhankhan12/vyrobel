<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerNextPayout;
use App\Support\Owner\OwnerOccupancyTrend;
use App\Support\Owner\OwnerStatementBreakdown;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $units = $portal->units();
        $symbol = $this->currencySymbol($statement->currency);
        $unitIds = array_map('strval', $units->pluck('id')->all());

        // The REAL per-unit split (net/gross/occupancy), via the ONE shared helper the PDF reuses too — it
        // reconciles to the statement net (page) / gross (PDF) to the cent (#17), owner's own units only (#28).
        $byUnit = OwnerStatementBreakdown::forStatement($statement);
        // The occupancy card + sparkline for the statement's OWN month (the same shared trend the Overview uses).
        $occupancy = OwnerOccupancyTrend::ending($unitIds, Carbon::parse($statement->period_start->toDateString()));

        return view('livewire.owner.statement', [
            'statement' => $this->statementPayload($statement, $symbol),
            'lines' => $this->linePayload($statement, $this->grossSublabel($byUnit, $units->count())),
            'history' => $this->historyPayload($allStatements, $statement->id),
            'years' => $this->years($allStatements),
            'totals' => $this->totals($allStatements),
            'nav' => $this->navigation($allStatements, $statement),
            'monthOptions' => $this->monthOptions($allStatements, $statement->id),
            'payout' => $this->payoutPayload($portal->payouts(), $statement, $symbol),
            'rail' => OwnerNextPayout::rail(app(OwnerContext::class)),
            'occupancy' => $occupancy,
            'unitsCount' => $units->count(),
            'byUnit' => $byUnit,
            'viewer' => $this->viewer(),
        ]);
    }

    /**
     * The gross line's REAL "N nights · M units" sublabel (the wireframe's gross detail) — nights = the room-nights
     * this statement's units sold in the period (the shared breakdown's total), units = the owner's unit count.
     * Null for a draft (no split yet) so the gross line simply carries no sublabel.
     *
     * @param  array<string, mixed>  $byUnit
     */
    private function grossSublabel(array $byUnit, int $unitsCount): ?string
    {
        if (($byUnit['available'] ?? false) !== true) {
            return null;
        }

        $nights = (int) ($byUnit['total_nights'] ?? 0);

        return $nights.' '.Str::plural('night', $nights)
            .' · '.$unitsCount.' '.Str::plural('unit', $unitsCount);
    }

    /**
     * The "jump to month" option list for the header switcher — the owner's REAL statements (id · month label ·
     * net figure), most recent first, each a link to that statement. Never invents a period.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array{id: string, label: string, net_display: string, is_current: bool}>
     */
    private function monthOptions(Collection $statements, string $currentId): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'id' => $s->id,
            'label' => $s->period_start->format('F Y'),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'is_current' => $s->id === $currentId,
        ])->values()->all();
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
    private function linePayload(OwnerStatement $s, ?string $grossSub): array
    {
        $gross = (int) $s->gross_minor;

        return OwnerStatementLine::query()
            ->where('owner_statement_id', $s->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function (OwnerStatementLine $l) use ($gross, $grossSub): array {
                $amount = (int) $l->amount_minor;
                $isNet = $l->line_type === 'net';

                return [
                    'line_type' => $l->line_type,
                    'description' => $l->description,
                    'sublabel' => $this->lineSublabel((string) $l->line_type, $amount, $gross, $grossSub),
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
     * The line's REAL sublabel: for GROSS, the "N nights · M units" detail (or null on a draft); for the
     * proportional deductions (commission / OTA), a truthful "% of gross" derived from the statement's own real
     * figures. Null for fixed or non-proportional lines. Never a stored or invented value.
     */
    private function lineSublabel(string $lineType, int $amount, int $gross, ?string $grossSub = null): ?string
    {
        if ($lineType === 'gross') {
            return $grossSub;
        }

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
