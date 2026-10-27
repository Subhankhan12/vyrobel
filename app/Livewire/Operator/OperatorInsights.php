<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\ReservationUnit;
use App\Support\Analytics\BookingCadence;
use App\Support\Analytics\CustomRange;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator INSIGHTS (Phase 6b · SG2) — the performance dashboard over the SG1 metric layer, scoped by the
 * Phase-6a property context (the selected property, or the all-properties roll-up via `currentReadIds`) and
 * a real date range. EVERY figure is real/derived from {@see PerformanceMetrics}; a thin/empty range shows an
 * honest "not enough data yet" state, never a fabricated curve, and the prototype's invented multi-year
 * history + generated narrative are NOT rendered. Batch B (GATE 3) lit up the REAL booking cadence via the
 * SHARED {@see BookingCadence} (identical to owner Insights, anti-drift): the median lead time, the
 * day-of-week occupancy, and the on-the-books revenue — every one a real aggregate, an honest empty state
 * when history is thin. #88 (the wireframe rebuild) added the **vs-last-year** deltas (real per month/KPI,
 * shown ONLY where prior-year data exists — never a fabricated delta against an empty year), the
 * occupancy-by-month LY series + on-the-books, the by-property table, forward pace, and the **real-derived
 * "Worth a look" insights** (peak weekday / departure-day concentration / forward revenue — each shown only
 * when the real data supports it). The wireframe's Expedia "costing €X" card + the Booking.com/Airbnb/Expedia
 * brand channel-split stay REFUSED (no connected channel, no OTA-brand data — #39): the channel mix shows the
 * REAL `source` values and the Expedia slot is an honest "connect a channel" note.
 *
 * TENANT-scoped; `/app` behind EnsureBackOfficeAccess (#26); the route carries `can:reports.view` (a
 * management/analytics baseline — NOT Front Desk / Housekeeping / Maintenance / Reservation Agent, #30).
 */
#[Layout('components.layouts.operator')]
class OperatorInsights extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $range = $this->resolveRange();
        $currency = $this->currency();
        $ids = $this->currentReadIds;

        $metrics = PerformanceMetrics::forProperties($ids, $range['start'], $range['end'], $currency);
        $trend = PerformanceMetrics::monthlyTrendForProperties($ids, $range['start'], $range['end'], $currency);

        // #88 — the SAME range one year earlier, for the "vs last year" deltas + the occupancy-by-month LY
        // series. REAL where prior-year data exists (the seeded prior-year stays); honest-omitted where it
        // doesn't (never a fabricated delta against an empty year, #39).
        $ly = PerformanceMetrics::forProperties($ids, $range['start']->subYear(), $range['end']->subYear(), $currency);
        $lyTrend = PerformanceMetrics::monthlyTrendForProperties($ids, $range['start']->subYear(), $range['end']->subYear(), $currency);

        // Batch B — REAL booking cadence via the SHARED BookingCadence (identical to owner Insights).
        $today = CarbonImmutable::today();
        $leadTime = BookingCadence::leadTimeForProperties($ids, $range['start'], $range['end']);
        $weekday = BookingCadence::weekdayOccupancyForProperties($ids, $range['start'], $range['end']);
        $onTheBooks = BookingCadence::onTheBooksForProperties($ids, $today, 90, $currency);

        return view('livewire.operator.insights', [
            'viewer' => $this->viewer(),
            'scopeLabel' => $this->viewingAllProperties ? __('php/livewire/operator/operator_insights.all_properties') : $this->currentPropertyName(),
            'ranges' => $this->rangeOptions($range['key']),
            // D3-1 — echo the submitted values so the fields keep what was typed after a GET, and flag a
            // rejected window so the page can say so rather than silently showing a preset.
            'customFrom' => (string) (request()->query(CustomRange::FROM) ?? ''),
            'customTo' => (string) (request()->query(CustomRange::TO) ?? ''),
            'customRejected' => CustomRange::requested(request()->query(CustomRange::FROM), request()->query(CustomRange::TO))
                && $range['key'] !== 'custom',
            'customMaxMonths' => CustomRange::MAX_MONTHS,
            'activeRange' => $range,
            'cards' => $this->cards($metrics, $ly, $leadTime, $currency),
            'channelMix' => $this->channelMix($metrics),
            'trend' => $this->trend($trend, $currency),
            'occByMonth' => $this->occByMonth($trend, $lyTrend, $today),
            'byProperty' => $this->byProperty($ids, $range, $currency),
            'pace' => $this->paceByProperty($ids, $today),
            'insights' => $this->derivedInsights($ids, $range, $weekday, $onTheBooks, $currency),
            'onTheBooks' => $this->money($onTheBooks, $currency),
            'leadTime' => $leadTime,
            'weekday' => $weekday,
            'multiCurrency' => $metrics->multiCurrency,
        ]);
    }

    /**
     * The 4 headline KPIs (#88, matching the wireframe): Occupancy · RevPAR · ADR · Booking window. The first
     * three carry a REAL "vs last year" delta — but ONLY when the same range one year earlier actually has
     * data ({@see MetricSet::isEmpty()}); otherwise the delta is null and the caption says so (never a
     * fabricated delta against an empty year, #39). All figures reconcile to the SG1 layer / FolioTotals.
     *
     * @param  array{has_data: bool, median_days: int|null}  $leadTime
     * @return list<array{key: string, label: string, value: string, delta: ?string, delta_dir: ?string, sub: string}>
     */
    private function cards(MetricSet $m, MetricSet $ly, array $leadTime, string $currency): array
    {
        // D3-4 — ⚠️ COVERAGE, not presence. `isEmpty()` requires all THREE of sold/bookings/available to be
        // zero, so a prior-year month with three days of spine and one sale is "not empty" and every delta
        // below was computed against an occupancy figure derived from those three days.
        $hasLy = ! $ly->isEmpty() && $ly->hasComparableCoverage();

        $occDelta = $hasLy ? $m->occupancyPct() - $ly->occupancyPct() : null;
        $revparDelta = $hasLy ? $m->revparMinor() - $ly->revparMinor() : null;
        $adrDelta = $hasLy ? $m->adrMinor() - $ly->adrMinor() : null;

        return [
            ['key' => 'occupancy', 'label' => __('php/livewire/operator/operator_insights.card_occupancy'), 'value' => $m->occupancyPct().'%',
                'delta' => $occDelta !== null ? __('php/livewire/operator/operator_insights.occupancy_delta', ['delta' => $this->signed($occDelta)]) : null, 'delta_dir' => $this->dir($occDelta), 'sub' => __('php/livewire/operator/operator_insights.occupancy_sub', ['sold' => $m->roomNightsSold, 'available' => $m->availableRoomNights])],
            ['key' => 'revpar', 'label' => __('php/livewire/operator/operator_insights.card_revpar'), 'value' => $this->money($m->revparMinor(), $currency),
                'delta' => $revparDelta !== null ? $this->signedMoney($revparDelta, $currency) : null, 'delta_dir' => $this->dir($revparDelta), 'sub' => __('php/livewire/operator/operator_insights.revpar_sub')],
            ['key' => 'adr', 'label' => __('php/livewire/operator/operator_insights.card_adr'), 'value' => $this->money($m->adrMinor(), $currency),
                'delta' => $adrDelta !== null ? $this->signedMoney($adrDelta, $currency) : null, 'delta_dir' => $this->dir($adrDelta), 'sub' => __('php/livewire/operator/operator_insights.adr_sub')],
            ['key' => 'window', 'label' => __('php/livewire/operator/operator_insights.card_booking_window'), 'value' => $leadTime['median_days'] !== null ? trans_choice('php/livewire/operator/operator_insights.window_days', $leadTime['median_days'], ['count' => $leadTime['median_days']]) : '—',
                'delta' => null, 'delta_dir' => null, 'sub' => $leadTime['has_data'] ? __('php/livewire/operator/operator_insights.window_sub') : __('php/livewire/operator/operator_insights.window_sub_no_data')],
        ];
    }

    // signed() / signedMoney() / dir() moved to the shared FormatsOperatorData trait (GATE 10.1 — Reports reuses them).

    /**
     * Real channel mix (room-nights by the reservation `source`), as shares — never fabricated.
     *
     * @return list<array{label: string, nights: int, pct: int}>
     */
    private function channelMix(MetricSet $m): array
    {
        $total = array_sum($m->sourceNights);
        if ($total === 0) {
            return [];
        }

        $mix = [];
        foreach ($m->sourceNights as $source => $nights) {
            $mix[] = ['label' => $this->sourceLabel($source), 'nights' => $nights, 'pct' => (int) round($nights / $total * 100)];
        }

        usort($mix, fn (array $a, array $b): int => $b['nights'] <=> $a['nights']);

        return $mix;
    }

    /**
     * The monthly trend — REAL months of real data. Honest thin-state when fewer than 2 months carry
     * activity (a 1-point line would mislead, the same reason pace is deferred).
     *
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $rows
     * @return array{enough: bool, max_minor: int, months: list<array{label: string, revenue_minor: int, revenue: string, occupancy_pct: int}>}
     */
    private function trend(array $rows, string $currency): array
    {
        $months = [];
        $withData = 0;
        $maxMinor = 0;

        foreach ($rows as $row) {
            $metrics = $row['metrics'];
            $rev = $metrics->roomRevenueMinor;
            if ($rev > 0 || $metrics->roomNightsSold > 0) {
                $withData++;
            }
            $maxMinor = max($maxMinor, $rev);
            $months[] = [
                'label' => $row['label'],
                'revenue_minor' => $rev,
                'revenue' => $this->money($rev, $currency),
                'occupancy_pct' => $metrics->occupancyPct(),
            ];
        }

        return ['enough' => $withData >= 2, 'max_minor' => $maxMinor, 'months' => $months];
    }

    /**
     * Occupancy by month (#88) — this-year occupancy + the on-the-books flag (a future month) + the REAL LY
     * occupancy for the same month-of-year, attached ONLY where prior-year data genuinely exists (never a
     * fabricated LY bar, #39). Mirrors the owner-Insights discipline.
     *
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $trend
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $lyTrend
     * @return array{enough: bool, has_ly: bool, months: list<array{label: string, occ: int, on_books: bool, ly: ?int}>}
     */
    private function occByMonth(array $trend, array $lyTrend, CarbonImmutable $today): array
    {
        // D3-4 — ⚠️ THE GUARD MOVED FROM PRESENCE TO COVERAGE, and the difference is the whole item.
        // `roomNightsSold > 0` asked "did anything sell?", which a 3-day spine answers YES to — so the month
        // published a percentage computed over 3 days under a label that reads as the month. It now asks
        // "was this period actually measured?" A month that WAS measured publishes its real figure however
        // low; a month that was not publishes nothing.
        $lyByMonthOfYear = [];
        foreach ($lyTrend as $row) {
            if ($row['metrics']->hasComparableCoverage()) {
                $lyByMonthOfYear[CarbonImmutable::parse($row['month'].'-01')->format('m')] = $row['metrics']->occupancyPct();
            }
        }

        $months = [];
        $hasLy = false;
        $withData = 0;
        foreach ($trend as $row) {
            $start = CarbonImmutable::parse($row['month'].'-01');
            $ly = $lyByMonthOfYear[$start->format('m')] ?? null;
            if ($ly !== null) {
                $hasLy = true;
            }
            if ($row['metrics']->roomNightsSold > 0) {
                $withData++;
            }
            $months[] = [
                'label' => $row['label'],
                'occ' => $row['metrics']->occupancyPct(),
                'on_books' => $start->greaterThan($today),   // a future month → its bookings are "on the books"
                'ly' => $ly,
            ];
        }

        // Honest thin-state — a chart with no real occupancy anywhere would be all-zero bars pretending to be data.
        return ['enough' => $withData >= 1, 'has_ly' => $hasLy, 'months' => $months];
    }

    /**
     * By-property table (#88) — real Occ/ADR/RevPAR per property in the range, + a Group row (the portfolio
     * roll-up) when there is more than one. Every figure a real aggregate through {@see PerformanceMetrics}.
     *
     * @param  list<string>  $ids
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return list<array{name: string, occ: string, adr: string, revpar: string, group: bool}>
     */
    private function byProperty(array $ids, array $range, string $currency): array
    {
        $rows = [];
        foreach ($ids as $pid) {
            $property = Property::query()->whereKey($pid)->first();
            if ($property === null) {
                continue;
            }
            $m = PerformanceMetrics::forProperties([$pid], $range['start'], $range['end'], $currency);
            $rows[] = ['name' => (string) $property->name, 'occ' => $m->occupancyPct().'%', 'adr' => $this->money($m->adrMinor(), $currency), 'revpar' => $this->money($m->revparMinor(), $currency), 'group' => false];
        }

        if (count($rows) > 1) {
            $g = PerformanceMetrics::forProperties($ids, $range['start'], $range['end'], $currency);
            $rows[] = ['name' => __('php/livewire/operator/operator_insights.group_row'), 'occ' => $g->occupancyPct().'%', 'adr' => $this->money($g->adrMinor(), $currency), 'revpar' => $this->money($g->revparMinor(), $currency), 'group' => true];
        }

        return $rows;
    }

    /**
     * Pace by property (#88) — forward occupancy over the next 30 days per property, vs the same window one
     * year earlier. The LY figure (and thus the ahead/behind verdict) shows ONLY where prior-year data exists;
     * otherwise the row is honestly "no prior-year pace".
     *
     * @param  list<string>  $ids
     * @return list<array{name: string, booked: int, ly: ?int, tone: string}>
     */
    private function paceByProperty(array $ids, CarbonImmutable $today): array
    {
        $from = $today;
        $to = $today->addDays(30);
        $currency = $this->currency();

        $rows = [];
        foreach ($ids as $pid) {
            $property = Property::query()->whereKey($pid)->first();
            if ($property === null) {
                continue;
            }
            $m = PerformanceMetrics::forProperties([$pid], $from, $to, $currency);
            $ly = PerformanceMetrics::forProperties([$pid], $from->subYear(), $to->subYear(), $currency);
            // D3-4 — ⚠️ A THIRD LY CONSUMER, which the ledger's entry did not name (it cited only the
            // occupancy-by-month guard). Without this the page would suppress the LY figure and still badge
            // a property "behind last year" against the same unmeasured number — the identical lie, one
            // component over. `$lyPct === null` already drives an honest 'flat' tone and a null column.
            $lyPct = $ly->isEmpty() || ! $ly->hasComparableCoverage() ? null : $ly->occupancyPct();
            $booked = $m->occupancyPct();
            $tone = $lyPct === null ? 'flat' : ($booked > $lyPct + 1 ? 'ahead' : ($booked < $lyPct - 1 ? 'behind' : 'onpace'));
            $rows[] = ['name' => (string) $property->name, 'booked' => $booked, 'ly' => $lyPct, 'tone' => $tone];
        }

        return $rows;
    }

    /**
     * "Worth a look" — REAL DERIVED observations (#88), each computed from real data and shown ONLY when the
     * data supports it. NEVER a fabricated insight: the wireframe's "Expedia disconnect costing €4,600" is
     * REFUSED (Expedia is not a connected channel, `source` has no OTA brand — no real data), replaced by an
     * honest channel-pace note. "Decisions stay yours" is the framing.
     *
     * @param  list<string>  $ids
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @param  array{has_data: bool, weekdays: list<array{label: string, pct: int}>}  $weekday
     * @return list<array{title: string, body: string, link: ?array{label: string, url: string}}>
     */
    private function derivedInsights(array $ids, array $range, array $weekday, int $onTheBooks, string $currency): array
    {
        $cards = [];

        // 1. The real peak night of the week — shown only when it genuinely runs hot (≥80%).
        if ($weekday['has_data']) {
            $peak = null;
            foreach ($weekday['weekdays'] as $w) {
                if ($peak === null || $w['pct'] > $peak['pct']) {
                    $peak = $w;
                }
            }
            if ($peak !== null && $peak['pct'] >= 80) {
                $cards[] = [
                    'title' => __('php/livewire/operator/operator_insights.peak_title', ['day' => $peak['label']]),
                    'body' => __('php/livewire/operator/operator_insights.peak_body', ['day' => $peak['label'], 'percent' => $peak['pct']]),
                    'link' => ['label' => __('php/livewire/operator/operator_insights.review_rates'), 'url' => route('app.rates')],
                ];
            }
        }

        // 2. The real departure-day concentration — shown only when turnover genuinely clusters.
        $dep = $this->departureConcentration($ids, $range);
        if ($dep !== null) {
            $cards[] = [
                'title' => __('php/livewire/operator/operator_insights.turnover_title', ['day' => $dep['label']]),
                'body' => __('php/livewire/operator/operator_insights.turnover_body', ['percent' => $dep['pct'], 'day' => $dep['label'], 'next_day' => $dep['next']]),
                'link' => ['label' => __('php/livewire/operator/operator_insights.balance_schedule'), 'url' => route('app.schedule')],
            ];
        }

        // 3. Forward revenue on the books — a real, plain figure (no suggestion invented).
        if ($onTheBooks > 0) {
            $cards[] = [
                'title' => __('php/livewire/operator/operator_insights.on_books_title', ['amount' => $this->money($onTheBooks, $currency)]),
                'body' => __('php/livewire/operator/operator_insights.on_books_body'),
                'link' => ['label' => __('php/livewire/operator/operator_insights.open_rates_availability'), 'url' => route('app.rates')],
            ];
        }

        return $cards;
    }

    /**
     * The real check-out day-of-week concentration over the range: the best two consecutive days as a share of
     * all departures. Null when there are too few departures to claim a pattern, or it isn't concentrated.
     *
     * @param  list<string>  $ids
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     * @return array{label: string, next: string, pct: int}|null
     */
    private function departureConcentration(array $ids, array $range): ?array
    {
        $lines = ReservationUnit::query()
            ->where('status', 'active')
            ->whereBetween('check_out', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->whereHas('reservation', fn ($q) => $q->whereIn('property_id', $ids)->whereNotIn('status', ['cancelled', 'no_show']))
            ->get(['check_out']);

        if ($lines->count() < 8) {
            return null;   // too thin to claim a pattern
        }

        $dow = array_fill(0, 7, 0);   // 0=Sun … 6=Sat
        foreach ($lines as $line) {
            $dow[(int) CarbonImmutable::parse($line->check_out)->dayOfWeek]++;
        }
        $total = array_sum($dow);

        $bestStart = 0;
        $bestSum = -1;
        for ($d = 0; $d < 7; $d++) {
            $sum = $dow[$d] + $dow[($d + 1) % 7];
            if ($sum > $bestSum) {
                $bestSum = $sum;
                $bestStart = $d;
            }
        }

        $pct = (int) round($bestSum / $total * 100);
        if ($pct < 45) {
            return null;   // not concentrated enough to be worth flagging
        }

        // The full weekday name in the reader's language (2023-01-01 was a Sunday, so day 0 = Sunday … 6 = Saturday).
        $name = fn (int $d): string => CarbonImmutable::create(2023, 1, 1)->addDays($d)->translatedFormat('l');

        return ['label' => $name($bestStart), 'next' => $name(($bestStart + 1) % 7), 'pct' => $pct];
    }

    /**
     * The selected date range. Default "this year" (the full calendar year) so the demo's real data is in
     * view; year-to-date and last-6-months are honest rolling windows.
     *
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $key = (string) request()->query('range', 'this-year');

        // D3-1 — a CUSTOM window takes precedence, and returns the SAME shape a preset does. Everything below
        // this method already consumes only `start`/`end`, so the custom range flows through the identical
        // metric calls, the identical span-shifted last-year comparison, and therefore D3-4's coverage guard
        // on the MetricSet those calls return. ⚠️ A second, custom-only metric path would have re-opened the
        // exact fake-full-month LY figure D3-4 just closed (§N.8f).
        // ⚠️ An unusable range resolves to null and falls through to the presets — a half-typed URL shows the
        // figures the user would have seen anyway, never an empty page.
        $custom = CustomRange::resolve(
            request()->query(CustomRange::FROM),
            request()->query(CustomRange::TO),
        );

        if ($custom !== null) {
            return $custom;
        }

        $year = $today->year;

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => __('php/livewire/operator/operator_insights.range_ytd'), 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => __('php/livewire/operator/operator_insights.range_6m'), 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            'summer' => ['key' => 'summer', 'label' => __('php/livewire/operator/operator_insights.range_summer'), 'start' => CarbonImmutable::create($year, 6, 1), 'end' => CarbonImmutable::create($year, 9, 1)],
            'q4' => ['key' => 'q4', 'label' => __('php/livewire/operator/operator_insights.range_q4'), 'start' => CarbonImmutable::create($year, 10, 1), 'end' => CarbonImmutable::create($year + 1, 1, 1)],
            default => ['key' => 'this-year', 'label' => $today->format('Y'), 'start' => $today->startOfYear(), 'end' => $today->startOfYear()->addYear()],
        };
    }

    /**
     * @return list<array{key: string, label: string, active: bool}>
     */
    private function rangeOptions(string $active): array
    {
        $year = CarbonImmutable::today()->format('Y');

        return [
            ['key' => 'ytd', 'label' => __('php/livewire/operator/operator_insights.range_ytd'), 'active' => $active === 'ytd'],
            ['key' => '6m', 'label' => __('php/livewire/operator/operator_insights.range_6m'), 'active' => $active === '6m'],
            ['key' => 'summer', 'label' => __('php/livewire/operator/operator_insights.range_summer'), 'active' => $active === 'summer'],
            ['key' => 'q4', 'label' => __('php/livewire/operator/operator_insights.range_q4'), 'active' => $active === 'q4'],
            ['key' => 'this-year', 'label' => __('php/livewire/operator/operator_insights.range_full_year', ['year' => $year]), 'active' => $active === 'this-year'],
        ];
    }

    private function currency(): string
    {
        $id = $this->currentReadIds[0] ?? null;
        $property = $id !== null ? Property::query()->whereKey($id)->first() : null;

        // ⚠️⚠️ F-44 (FIX 46) — FOUND BY THE GUARD, NOT BY THE REGISTER. The nine recorded sites were
        //    `$collection->first()`; this is the same defect one shape over — a `->first()` query that finds
        //    NOTHING when no property is accessible, so the literal fires. ⚠️ Invisible to the schema for the
        //    same reason: `properties.currency` is NOT NULL, and a row that does not exist has no column.
        //    ⚠️ Nothing is in scope to derive from here, so the literal becomes the ONE product default.
        return (string) ($property->currency ?? config('billing.default_currency', 'EUR'));
    }

    private function currentPropertyName(): string
    {
        $property = $this->currentProperty();

        return $property !== null ? (string) $property->name : __('php/livewire/operator/operator_insights.no_property');
    }
}
