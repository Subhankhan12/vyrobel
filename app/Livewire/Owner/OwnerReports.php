<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Services\OwnerVatService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use App\Support\Notifications\NotificationFeed;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerNextPayout;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner REPORTS (exact-match pass · invariant #61 → deepened to the full "Owner Reports" wireframe, #63) — the
 * owner's REAL analytics for their OWN units. OWNER-SCOPED via {@see OwnerPortalService::units()} + the
 * owner-scoped {@see OwnerLedgerEntry} (never a request id; an owner sees only their own). Every figure
 * reconciles by construction to the owner statements/ledger (#17) — the Batch-B analytics layer, reused
 * (anti-drift). Read-only: the reporting-year / custom-date-range / export are all GETs, so there is NO write
 * to gate (#20 has nothing to guard here).
 *
 * HONESTY MAP (see docs/OWNER-REPORTS-AUDIT.md):
 *   • REAL — the KPIs (net earned / avg occupancy / nights sold), the net-by-month chart, per-unit performance
 *     (ADR/RevPAR/nights/occupancy/net/share), the GROSS-TO-NET (reconciles #17, with the REAL agreement
 *     commission rate — never a hardcoded 15%), the payout summary (masked IBAN CAP_PAYOUT-only #28), the real
 *     Documents links + exports (CAP_EXPORTS-gated #42). The notification popover is the shared shell bell.
 *   • REAL, NEWLY WIRED — **net-by-channel**: each owner-ledger entry attributed to its reservation's real
 *     `source` (Direct / OTA / Corporate / Walk-in …) or an honest "Other" bucket for non-reservation rows, so
 *     Σ(channels) == the period net EXACTLY (a decomposition of the same net, not a parallel figure). Replaces
 *     the prior nights-by-channel — a stale-refusal re-verification win.
 *   • HONEST-OMIT — the wireframe's prior-year comparison (chart dashed bars + KPI "+Δ" + hero YoY badge): the
 *     demo owner ledger has NO prior-year data, so no comparison is fabricated (#39); the series appears only
 *     when real prior-year data exists. The year dropdown lists the owner's REAL statement years, never the
 *     wireframe's hardcoded 2024/2023.
 *   • REAL (Wave D · tax engine) — the **Tax & VAT summary** is now COMPUTED (output/input/net VAT per quarter)
 *     from FOUNDER-CONFIGURED rates × the owner's real gross/commission ({@see OwnerVatService}), honestly labelled
 *     "computed from your configured rates — verify with your accountant; not tax advice". An EXAMPLE-rate figure is
 *     flagged "not for filing"; a not-registered / not-configured owner gets the honest state (no fabricated figure,
 *     #39). "Email to me" queues the owner's latest STATEMENT PDF to their own inbox (GATE 4).
 */
#[Layout('components.layouts.owner')]
class OwnerReports extends Component
{
    use FormatsOwnerData;

    /** The channel-swatch palette (the wireframe's warm terracotta ramp), assigned by net rank. */
    private const CHANNEL_COLORS = ['#B4400F', '#DB6B2E', '#E8A24A', '#CBB29B', '#8A2D0C'];

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed
        $currency = $this->currency($units);
        $range = $this->resolveRange();
        $unitIds = $units->map(fn (Unit $u): string => (string) $u->id)->all();

        // Owner-scoped ledger — the single source of "earnings". Loaded once; clipped in-memory for the period
        // and the prior-year comparison so every figure derives from the SAME rows (reconciles by construction).
        $allEntries = OwnerLedgerEntry::query()->get();
        $rangeEntries = $allEntries->filter(fn (OwnerLedgerEntry $e): bool => $this->inRange($e, $range['start'], $range['end']))->values();
        $priorEntries = $allEntries->filter(fn (OwnerLedgerEntry $e): bool => $this->inRange($e, $range['start']->subYear(), $range['end']->subYear()))->values();

        $metrics = PerformanceMetrics::forOwnerUnits($unitIds, $range['start'], $range['end'], $currency);
        $netByUnit = $this->netByUnit($rangeEntries);
        $totalNet = OwnerLedgerAttribution::netDisplays($rangeEntries);
        $totalNetMinor = OwnerLedgerAttribution::netPerCurrency($rangeEntries)[$currency] ?? 0;

        $user = request()->user();

        return view('livewire.owner.reports', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'currency' => $currency,
            'yearOptions' => $this->yearOptions($range),
            'rangeOptions' => $this->rangeOptions($range),
            'activeRange' => $range,
            'monthOptions' => $this->monthOptions(),
            'kpis' => [
                'net' => $totalNet === [] ? $this->money(0, $currency) : implode(' · ', $totalNet),
                'net_period' => $range['label'],
                'occupancy' => $metrics->occupancyPct().'%',
                'nights' => $metrics->roomNightsSold,
                // YoY deltas are HONEST-OMITTED unless real prior-year data exists (#39 — never a fabricated %).
                'net_delta' => $this->yoyDelta($totalNetMinor, OwnerLedgerAttribution::netPerCurrency($priorEntries)[$currency] ?? 0, $priorEntries->isNotEmpty()),
            ],
            'netByMonth' => $this->netByMonth($rangeEntries, $priorEntries, $range, $currency),
            'perUnit' => $this->perUnitRows($units, $range, $currency, $netByUnit),
            'perfHeader' => ['adr' => $this->money($metrics->adrMinor(), $currency), 'revpar' => $this->money($metrics->revparMinor(), $currency)],
            'allUnitsNet' => $totalNet === [] ? $this->money(0, $currency) : implode(' · ', $totalNet),
            'byChannel' => $this->channelNet($rangeEntries, $currency),
            'grossToNet' => $this->grossToNet($rangeEntries, $currency),
            'commissionLabel' => $this->commissionLabel(),
            'hero' => [
                'eyebrow' => $range['label'].' so far',
                'net' => $totalNet === [] ? $this->money(0, $currency) : implode(' · ', $totalNet),
                'sub' => trim($metrics->roomNightsSold.' '.Str::plural('night', $metrics->roomNightsSold).' · '.$units->count().' '.Str::plural('unit', $units->count())),
            ],
            'payout' => $this->payoutSummary($currency),
            'documents' => $this->recentDocuments(),
            'exportItems' => $this->exportItems($range),
            // The owner's REAL stored tax country (plain string; NOT the encrypted VAT number).
            'taxCountry' => $units->first()?->owner?->tax_country,
            // Wave D · tax engine — the REAL VAT summary, computed from FOUNDER-CONFIGURED rates × real amounts
            // (OwnerVatService), or an honest not-registered/not-configured state. Never a fabricated figure (#39).
            'vat' => ($vatOwner = $units->first()?->owner) instanceof Owner
                ? app(OwnerVatService::class)->summary($vatOwner, (int) $range['start']->year)
                : ['available' => false, 'status' => 'not_configured', 'quarters' => [], 'is_example' => false],
            'notifications' => $user !== null ? NotificationFeed::recentForUser($user, 4) : ['items' => [], 'unread' => 0],
        ]);
    }

    /** A ledger entry falls in `[start, end)` when its service_date does. */
    private function inRange(OwnerLedgerEntry $entry, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $date = CarbonImmutable::parse((string) $entry->service_date);

        return $date->greaterThanOrEqualTo($start) && $date->lessThan($end);
    }

    /** A YoY delta string ("+12%" / "−4%") — ONLY when real prior-year data exists, else null (never fabricated). */
    private function yoyDelta(int $current, int $prior, bool $hasPrior): ?string
    {
        if (! $hasPrior || $prior === 0) {
            return null;
        }
        $pct = (int) round(($current - $prior) / abs($prior) * 100);

        return ($pct >= 0 ? '+' : '−').abs($pct).'%';
    }

    /**
     * Net per currency PER UNIT — the owner's ledger grouped by unit, each summed through the ONE shared
     * attribution rule so a per-unit figure and the total can never disagree.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array<string, array<string, int>> unitId → (currency → net minor)
     */
    private function netByUnit(Collection $entries): array
    {
        $out = [];

        foreach ($entries->groupBy('unit_id') as $unitId => $group) {
            if ((string) $unitId === '') {
                continue;
            }
            $out[(string) $unitId] = OwnerLedgerAttribution::netPerCurrency($group);
        }

        return $out;
    }

    /**
     * Net earnings by MONTH across the range — Σ signedMinor() of the owner's ledger entries per calendar month,
     * in the display currency (reconciles to the total by construction). Bars scale to the max month; the peak
     * month is flagged. The prior-year series is included ONLY when real prior-year data exists (#39).
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @param  Collection<int, OwnerLedgerEntry>  $priorEntries
     * @return array{months: list<array<string, mixed>>, has_data: bool, has_prior: bool, prior_year_label: string, chart_range: string}
     */
    private function netByMonth(Collection $entries, Collection $priorEntries, array $range, string $currency): array
    {
        $sumByMonth = function (Collection $set, CarbonImmutable $start) use ($currency): array {
            $out = [];
            $cursor = $start->startOfMonth();
            for ($i = 0; $i < 12; $i++) {
                $out[$cursor->format('Y-m')] = 0;
                $cursor = $cursor->addMonth();
            }
            foreach ($set as $entry) {
                if ((string) $entry->currency !== $currency) {
                    continue;   // never mix currencies into one bar series (#17)
                }
                $key = CarbonImmutable::parse((string) $entry->service_date)->format('Y-m');
                if (array_key_exists($key, $out)) {
                    $out[$key] += $entry->signedMinor();
                }
            }

            return $out;
        };

        $current = $sumByMonth($entries, $range['start']);
        $prior = $sumByMonth($priorEntries, $range['start']->subYear());
        $priorVals = array_values($prior);

        $maxAbs = 0;
        foreach ($current as $v) {
            $maxAbs = max($maxAbs, abs($v));
        }
        foreach ($prior as $v) {
            $maxAbs = max($maxAbs, abs($v));
        }
        $peakKey = null;
        $peak = 0;
        foreach ($current as $k => $v) {
            if ($v > $peak) {
                $peak = $v;
                $peakKey = $k;
            }
        }

        $hasPrior = array_sum(array_map('abs', $priorVals)) > 0;

        $months = [];
        $withData = 0;
        $idx = 0;
        foreach ($current as $key => $net) {
            if ($net !== 0) {
                $withData++;
            }
            $priorNet = $priorVals[$idx] ?? 0;
            $months[] = [
                'label' => CarbonImmutable::parse($key.'-01')->format('M'),
                'net_display' => $this->money($net, $currency),
                'pct' => $maxAbs > 0 ? (int) round(abs($net) / $maxAbs * 100) : 0,
                'prior_pct' => $hasPrior && $maxAbs > 0 ? (int) round(abs($priorNet) / $maxAbs * 100) : 0,
                'is_peak' => $key === $peakKey && $peak > 0,
            ];
            $idx++;
        }

        return [
            'months' => $months,
            'has_data' => $withData > 0,
            'has_prior' => $hasPrior,
            'prior_year_label' => (string) $range['start']->subYear()->year,
            'chart_range' => $range['label'],
        ];
    }

    /**
     * Per-unit performance rows — ADR / RevPAR / nights / occupancy / net / share of net, all REAL per-unit
     * aggregates. Share is this unit's net as a % of the owner's total net (display currency), so the shares
     * reconcile to 100% of the real total.
     *
     * @param  Collection<int, Unit>  $units
     * @param  array<string, array<string, int>>  $netByUnit
     * @return list<array<string, mixed>>
     */
    private function perUnitRows(Collection $units, array $range, string $currency, array $netByUnit): array
    {
        $totalNetMinor = 0;
        foreach ($netByUnit as $byCurrency) {
            $totalNetMinor += $byCurrency[$currency] ?? 0;
        }

        return $units->map(function (Unit $unit) use ($range, $currency, $netByUnit, $totalNetMinor): array {
            $m = PerformanceMetrics::forOwnerUnits([(string) $unit->id], $range['start'], $range['end'], $currency);
            $net = $netByUnit[(string) $unit->id] ?? [];
            $netMinor = $net[$currency] ?? 0;
            $name = $unit->name !== null ? (string) $unit->name : (string) $unit->code;
            $code = (string) $unit->code;

            return [
                'name' => $name,
                'sub' => ($code !== '' && $code !== $name) ? $code : (string) ($unit->property->name ?? ''),
                'adr' => $this->money($m->adrMinor(), $currency),
                'revpar' => $this->money($m->revparMinor(), $currency),
                'nights' => $m->roomNightsSold,
                'occupancy_pct' => $m->occupancyPct(),
                'net' => $net === [] ? '—' : implode(' · ', array_map(fn (int $minor, string $c): string => $this->money($minor, $c), array_values($net), array_keys($net))),
                'share_pct' => $totalNetMinor > 0 ? (int) round($netMinor / $totalNetMinor * 100) : 0,
            ];
        })->all();
    }

    /**
     * Booking sources — NET by channel. Each owner-ledger entry is attributed to its reservation's REAL
     * `source` (or an honest "Other" bucket for non-reservation rows), and the buckets are summed via
     * signedMinor(). Σ(buckets) == the period net EXACTLY — this is a decomposition of the SAME net shown
     * everywhere, never a parallel figure (#17). The bar shows each positive bucket's share; a net-negative
     * bucket (e.g. a costs-only "Other") is listed with its real amount but contributes no bar segment.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array{rows: list<array<string, mixed>>, gradient: string, has_data: bool, total: string}
     */
    private function channelNet(Collection $entries, string $currency): array
    {
        $inCurrency = $entries->filter(fn (OwnerLedgerEntry $e): bool => (string) $e->currency === $currency)->values();
        if ($inCurrency->isEmpty()) {
            return ['rows' => [], 'gradient' => 'rgba(120,70,40,.1)', 'has_data' => false, 'total' => $this->money(0, $currency)];
        }

        // Resolve each entry's reservation source once — a keyed map (owner/tenant-scoped by the Reservation
        // global scope; the entries are already owner-scoped, so this only ever sees this owner's reservations).
        $reservationIds = $inCurrency->pluck('reservation_id')->filter()->unique()->values()->all();
        $sourceById = Reservation::query()->whereIn('id', $reservationIds)->pluck('source', 'id');

        /** @var array<string, int> $buckets  label → net minor */
        $buckets = [];
        foreach ($inCurrency as $entry) {
            $source = $entry->reservation_id !== null ? $sourceById->get((string) $entry->reservation_id) : null;
            $label = $source !== null ? $this->sourceLabel((string) $source) : 'Other';
            $buckets[$label] = ($buckets[$label] ?? 0) + $entry->signedMinor();
        }
        arsort($buckets);

        $positiveSum = array_sum(array_map(fn (int $v): int => max(0, $v), $buckets));
        $rows = [];
        $stops = [];
        $cursor = 0;
        $i = 0;
        foreach ($buckets as $label => $net) {
            $pct = $positiveSum > 0 ? (int) round(max(0, $net) / $positiveSum * 100) : 0;
            $color = self::CHANNEL_COLORS[$i % count(self::CHANNEL_COLORS)];
            $rows[] = ['label' => $label, 'amount' => $this->money($net, $currency), 'pct' => $pct, 'color' => $color];
            if ($net > 0 && $pct > 0) {
                $stops[] = $color.' '.$cursor.'%';
                $cursor += $pct;
                $stops[] = $color.' '.$cursor.'%';
            }
            $i++;
        }

        return [
            'rows' => $rows,
            'gradient' => $stops === [] ? 'rgba(120,70,40,.1)' : 'linear-gradient(90deg, '.implode(', ', $stops).')',
            'has_data' => true,
            'total' => $this->money((int) array_sum($buckets), $currency),
        ];
    }

    /**
     * Gross-to-net for the range, in the display currency — bucketed from the REAL owner ledger so
     * gross + commission + fees + adjustments == net EXACTLY (Σ signedMinor over every entry — #17). The
     * commission line carries the REAL agreement rate (never a hardcoded 15%).
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array<string, mixed>
     */
    private function grossToNet(Collection $entries, string $currency): array
    {
        $inCurrency = $entries->filter(fn (OwnerLedgerEntry $e): bool => (string) $e->currency === $currency);

        $sum = fn (array $types): int => (int) $inCurrency
            ->filter(fn (OwnerLedgerEntry $e): bool => in_array((string) $e->entry_type, $types, true))
            ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);

        $gross = $sum(['room_revenue', 'addon']);
        $commission = $sum(['commission']);
        $fees = $sum(['ota_cost', 'management_fee']);
        $adjustments = $sum(['cleaning', 'expense', 'adjustment']);
        $net = (int) $inCurrency->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);

        return [
            'gross' => MoneyFormatter::display($gross, $currency),
            'commission' => MoneyFormatter::display($commission, $currency, true),
            'fees' => MoneyFormatter::display($fees, $currency, true),
            'adjustments' => MoneyFormatter::display($adjustments, $currency, true),
            'has_adjustments' => $adjustments !== 0,
            'net' => MoneyFormatter::display($net, $currency),
            // reconciliation guarantee: the four buckets sum to net (every entry_type is in exactly one bucket).
            'reconciles' => $gross + $commission + $fees + $adjustments === $net,
        ];
    }

    /**
     * The owner's CURRENT commission rate, from the effective {@see ManagementAgreement} (the REAL
     * per-owner rate — 18% in the demo, NOT the wireframe's hardcoded 15%). A null/ambiguous set omits the rate
     * label rather than guessing.
     */
    private function commissionLabel(): string
    {
        $percent = OwnerCommissionRate::percent(app(OwnerContext::class));

        return $percent === null ? 'Commission' : 'Commission · '.$percent.'%';
    }

    /**
     * The payout summary — the owner's most recent real {@see Payout} (amount + status + date) + the real
     * paid-this-year total + the masked payout method (CAP_PAYOUT-only #28). Owner-scoped; honest zeros/nulls.
     *
     * @return array{amount: ?string, status: ?string, status_key: string, paid_year: string, method_masked: ?string, date: ?string}
     */
    private function payoutSummary(string $currency): array
    {
        $payouts = Payout::query()->orderByDesc('created_at')->get();
        $latest = $payouts->first();

        $paidYear = (int) $payouts
            ->filter(fn (Payout $p): bool => $p->status === 'paid' && (int) $p->created_at?->year === (int) now()->year)
            ->sum('amount_minor');

        $status = $latest instanceof Payout ? (string) $latest->status : null;
        $date = null;
        if ($latest instanceof Payout) {
            $stamp = $latest->paid_at ?? $latest->created_at;
            $date = $stamp?->format('j M Y');
        }

        return [
            'amount' => $latest instanceof Payout ? MoneyFormatter::display((int) $latest->amount_minor, (string) $latest->currency) : null,
            'status' => $status !== null ? ucfirst($status) : null,
            'status_key' => $status === 'paid' ? 'paid' : 'pending',
            'paid_year' => MoneyFormatter::display($paidYear, $currency),
            'method_masked' => OwnerNextPayout::rail(app(OwnerContext::class))['iban_masked'],
            'date' => $date,
        ];
    }

    /**
     * A compact list of the most recent REAL statement PDFs (owner-scoped, finalized/paid only) — each a real
     * CAP_EXPORTS-gated download. The full library lives on the Documents page.
     *
     * @return list<array{name: string, sub: string, href: string}>
     */
    private function recentDocuments(): array
    {
        return OwnerStatement::query()
            ->whereIn('status', ['finalized', 'paid'])
            ->orderByDesc('period_start')
            ->limit(3)
            ->get()
            ->map(fn (OwnerStatement $s): array => [
                'name' => $s->period_start->format('F Y').' statement',
                'sub' => 'PDF · net & payout',
                'href' => route('owner.statements.pdf', $s->id),
            ])
            ->all();
    }

    /**
     * The Export menu items — the REAL owner exports wired to their routes, honest-labelled where a dedicated
     * format has no backing (Excel = the CSV; PDF pack + Email = honest "Soon"; All statements = the archive).
     * Each carries `href` (a real route) OR `soon` = true; never a link that downloads nothing (#39).
     *
     * @return list<array{key: string, title: string, sub: string, href: ?string, soon: bool}>
     */
    private function exportItems(array $range): array
    {
        $year = (int) $range['start']->year;

        return [
            ['key' => 'pdf', 'title' => 'PDF report', 'sub' => 'Formatted earnings summary', 'href' => route('owner.statements.history.pdf'), 'soon' => false],
            ['key' => 'csv', 'title' => 'CSV', 'sub' => 'Monthly & per-unit rows', 'href' => route('owner.statements.history.csv'), 'soon' => false],
            ['key' => 'xls', 'title' => 'Spreadsheet', 'sub' => 'Opens the CSV in Excel/Sheets', 'href' => route('owner.statements.history.csv'), 'soon' => false],
            ['key' => 'tax', 'title' => 'Annual tax summary', 'sub' => $year.' · for your accountant', 'href' => route('owner.tax.annual', $year), 'soon' => false],
            ['key' => 'all', 'title' => 'All statements', 'sub' => 'Open your Documents', 'href' => route('owner.documents'), 'soon' => false],
            ['key' => 'pack', 'title' => 'PDF pack', 'sub' => 'Bundled statements', 'href' => null, 'soon' => true],
            // "Email to me" is REAL now (GATE 4) — a POST that queues the owner's latest statement PDF to their inbox.
            ['key' => 'email', 'title' => 'Email to me', 'sub' => 'Send my latest statement to my inbox', 'href' => null, 'post' => route('owner.reports.email'), 'soon' => false],
        ];
    }

    /**
     * Resolve the active reporting window. Read-only GET params, in priority order:
     *   from=YYYY-MM & to=YYYY-MM  → a real custom range · range=ytd|6m → those windows · year=YYYY → a
     *   calendar year · else → the current calendar year (the default the wireframe's year dropdown lands on).
     *
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable, custom: bool, from: ?string, to: ?string}
     */
    private function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $from = $this->parseMonth((string) request()->query('from', ''));
        $to = $this->parseMonth((string) request()->query('to', ''));

        if ($from !== null && $to !== null) {
            $start = $from->startOfMonth();
            $end = $to->startOfMonth()->addMonth();   // inclusive of the "to" month
            if ($end->lessThanOrEqualTo($start)) {
                $end = $start->addMonth();
            }

            return ['key' => 'custom', 'label' => $start->format('M Y').' – '.$to->startOfMonth()->format('M Y'), 'start' => $start, 'end' => $end, 'custom' => true, 'from' => $start->format('Y-m'), 'to' => $to->startOfMonth()->format('Y-m')];
        }

        $range = (string) request()->query('range', '');
        if ($range === 'ytd') {
            return ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay(), 'custom' => false, 'from' => null, 'to' => null];
        }
        if ($range === '6m') {
            return ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay(), 'custom' => false, 'from' => null, 'to' => null];
        }

        $year = (int) request()->query('year', (string) $today->year);
        if ($year < 2000 || $year > $today->year + 1) {
            $year = (int) $today->year;
        }
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();

        return ['key' => 'year:'.$year, 'label' => (string) $year, 'start' => $start, 'end' => $start->addYear(), 'custom' => false, 'from' => null, 'to' => null];
    }

    /** Parse a "YYYY-MM" month param to an immutable date, or null when absent/malformed. */
    private function parseMonth(string $value): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value.'-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The REPORTING-YEAR dropdown — the owner's REAL statement years (never the wireframe's fabricated
     * 2024/2023). Each links `?year=YYYY`; the current year is always present even before its first statement.
     *
     * @return list<array{key: string, label: string, sub: string, active: bool, href: string}>
     */
    private function yearOptions(array $range): array
    {
        $thisYear = (int) CarbonImmutable::today()->year;

        // Real statement counts per year (owner-scoped), so the sub-label ("N statements") is never invented.
        $counts = OwnerStatement::query()
            ->whereIn('status', ['finalized', 'paid'])
            ->get(['period_start'])
            ->groupBy(fn (OwnerStatement $s): int => (int) $s->period_start->year)
            ->map(fn (Collection $g): int => $g->count());

        $years = collect($counts->keys()->all())->push($thisYear)->unique()->sortDesc()->values();

        return $years->map(function (int $year) use ($range, $counts, $thisYear): array {
            $n = (int) ($counts[$year] ?? 0);
            $sub = $n === 0 ? 'No statements yet' : $n.' '.Str::plural('statement', $n).($year === $thisYear ? ' YTD' : '');

            return [
                'key' => 'year:'.$year,
                'label' => (string) $year,
                'sub' => $sub,
                'active' => $range['key'] === 'year:'.$year,
                'href' => route('owner.reports', ['year' => $year]),
            ];
        })->all();
    }

    /**
     * The quick range options offered alongside the year dropdown (real windows over the same ledger).
     *
     * @return list<array{key: string, label: string, active: bool, href: string}>
     */
    private function rangeOptions(array $range): array
    {
        return [
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $range['key'] === 'ytd', 'href' => route('owner.reports', ['range' => 'ytd'])],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $range['key'] === '6m', 'href' => route('owner.reports', ['range' => '6m'])],
        ];
    }

    /**
     * The month choices for the custom From/To popover — the last 24 months through this month, newest first.
     *
     * @return list<array{value: string, label: string}>
     */
    private function monthOptions(): array
    {
        $cursor = CarbonImmutable::today()->startOfMonth();
        $out = [];
        for ($i = 0; $i < 24; $i++) {
            $out[] = ['value' => $cursor->format('Y-m'), 'label' => $cursor->format('M Y')];
            $cursor = $cursor->subMonth();
        }

        return $out;
    }

    /**
     * @param  Collection<int, Unit>  $units
     */
    private function currency(Collection $units): string
    {
        $first = $units->first();

        return (string) ($first?->property->currency ?? 'EUR');
    }
}
