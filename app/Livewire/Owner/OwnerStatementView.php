<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerNextPayout;
use App\Support\Owner\OwnerOccupancyTrend;
use App\Support\Owner\OwnerStatementBreakdown;
use App\Support\Pdf\OwnerStatementHistoryDocument;
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
 * ⚠️ STALE CLAIM CORRECTED (design pass · Owner-2). This block used to read "DEFERRED (visually stubbed,
 * honestly labelled — no fake data): by-unit split, occupancy chart, and the history table's CSV export
 * (no CSV builder yet)." All THREE have since shipped and are real: the by-unit split is the shared
 * {@see OwnerStatementBreakdown} (reconciling, and the PDF reads the same one), the occupancy card is the
 * shared {@see OwnerOccupancyTrend}, and the CSV export is a real owner-scoped route
 * (`owner.statements.history.csv`, CAP_EXPORTS). Nothing on this screen is stubbed.
 * The "Payment & ops fees" line is a REAL reconciling bucket (Wave C · GATE 4) — the signed sum of the
 * period's `payment_fee` ledger lines (card-processing / platform-ops fees), included in net by
 * construction. It is NEVER fabricated: until a live-payment writer posts such a line the bucket is a
 * genuine €0 and the line is omitted (the same omit-empty rule as every other component).
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
            'lines' => $this->linePayload($statement, $this->grossSublabel($byUnit)),
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
     * The gross line's REAL "N nights · M units" sublabel (the wireframe's gross detail) — nights + units both from
     * the shared breakdown (the units that EARNED this statement), so the PAGE gross line and the PDF's gross detail
     * carry the SAME count (anti-drift). Null for a draft (no split yet) → the gross line carries no sublabel.
     *
     * @param  array<string, mixed>  $byUnit
     */
    private function grossSublabel(array $byUnit): ?string
    {
        if (($byUnit['available'] ?? false) !== true) {
            return null;
        }

        $nights = (int) ($byUnit['total_nights'] ?? 0);
        $units = (int) ($byUnit['unit_count'] ?? 0);

        return __('php/livewire/owner/owner_statement_view.nights_units', [
            'nights' => trans_choice('php/livewire/owner/owner_statement_view.nights', $nights, ['count' => $nights]),
            'units' => trans_choice('php/livewire/owner/owner_statement_view.units', $units, ['count' => $units]),
        ]);
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
            'label' => $s->period_start->translatedFormat('F Y'),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'is_current' => $s->id === $currentId,
        ])->values()->all();
    }

    /** The statement's date span as people read it, e.g. "1 Sep – 30 Sep 2026". */
    private function dateRange(OwnerStatement $s): string
    {
        return __('php/livewire/owner/owner_statement_view.date_range', [
            'start' => $s->period_start->translatedFormat('j M'),
            'end' => $s->period_end->translatedFormat('j M Y'),
        ]);
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
            'period_label' => $s->period_start->translatedFormat('F Y'),
            'period_range' => $this->dateRange($s),
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

        return $lineType === 'commission'
            ? __('php/livewire/owner/owner_statement_view.pct_of_gross_room_revenue', ['pct' => $pct])
            : __('php/livewire/owner/owner_statement_view.pct_of_gross_revenue', ['pct' => $pct]);
    }

    /**
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function historyPayload(Collection $statements, string $currentId): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'id' => $s->id,
            'month_label' => $s->period_start->translatedFormat('M Y'),
            'period_range' => $this->dateRange($s),
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
     * The history net total — Σ `net_payable_minor` grouped by PAYOUT currency, rendered per currency (never a
     * cross-currency sum: that would be a silent FX the backend never made, #17 — the same discipline as
     * {@see OwnerStatementHistoryDocument} and OwnerArchive). Single-currency owners (the common
     * case) get one figure exactly as before.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return array{count: int, net_total_display: string}
     */
    /**
     * The owner's own display currency when there is no statement to read one from — their units' property
     * currency, the same rule {@see OwnerMyUnits} and {@see OwnerInsights} use, so the screens agree.
     * `properties.currency` is NOT NULL; the final fallback only covers an owner with no units at all.
     */
    private function ownerPortfolioCurrency(): string
    {
        return (string) (Unit::query()->with('property')->first()?->property->currency
            ?? config('billing.default_currency', 'EUR'));
    }

    private function totals(Collection $statements): array
    {
        $perCurrency = $statements
            ->groupBy(fn (OwnerStatement $s): string => (string) $s->currency)
            ->map(fn (Collection $group, string $currency): string => $this->money(
                (int) $group->sum(fn (OwnerStatement $s): int => (int) $s->net_payable_minor),
                $currency,
            ))
            ->values()
            ->all();

        return [
            'count' => $statements->count(),
            // ⚠️ The no-statements zero was formatted as `money(0, 'EUR')` — a literal passed to a formatter
            // whose currency argument is required, so a GBP owner with no statements yet was shown "€0.00".
            // With no statement there is no statement currency, so fall back to the owner's own units'
            // property currency (the {@see OwnerMyUnits} rule) rather than asserting one.
            'net_total_display' => $perCurrency === [] ? $this->money(0, $this->ownerPortfolioCurrency()) : implode(' · ', $perCurrency),
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
                ? ['id' => $s->id, 'label' => $s->period_start->translatedFormat('F Y')]
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
                'rail' => $payout->rail === 'manual' ? __('php/livewire/owner/owner_statement_view.bank_transfer') : (string) $payout->rail,
                'paid_at' => $paidAt !== null ? Carbon::parse($paidAt)->translatedFormat('j M Y') : null,
            ];
        }

        // No payout row yet: derive an honest "awaiting"/"draft" state from the statement itself.
        return [
            'id' => null,   // no matched payout ⇒ no remittance advice to download yet
            'amount_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'status_meta' => $s->status === 'draft'
                ? ['label' => __('php/livewire/owner/owner_statement_view.not_yet_finalized'), 'tone' => 'draft']
                : ['label' => __('php/livewire/owner/owner_statement_view.awaiting_payout'), 'tone' => 'pending'],
            'rail' => null,
            'paid_at' => null,
        ];
    }
}
