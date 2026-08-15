<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\ReservationUnit;
use App\Support\Analytics\BookingCadence;
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
            'scopeLabel' => $this->viewingAllProperties ? 'All properties' : $this->currentPropertyName(),
            'ranges' => $this->rangeOptions($range['key']),
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
            ['key' => 'occupancy', 'label' => 'Occupancy', 'value' => $m->occupancyPct().'%',
                'delta' => $occDelta !== null ? $this->signed($occDelta).' pts' : null, 'delta_dir' => $this->dir($occDelta), 'sub' => $m->roomNightsSold.' of '.$m->availableRoomNights.' room-nights'],
            ['key' => 'revpar', 'label' => 'RevPAR', 'value' => $this->money($m->revparMinor(), $currency),
                'delta' => $revparDelta !== null ? $this->signedMoney($revparDelta, $currency) : null, 'delta_dir' => $this->dir($revparDelta), 'sub' => 'revenue per available room'],
            ['key' => 'adr', 'label' => 'ADR', 'value' => $this->money($m->adrMinor(), $currency),
                'delta' => $adrDelta !== null ? $this->signedMoney($adrDelta, $currency) : null, 'delta_dir' => $this->dir($adrDelta), 'sub' => 'achieved, selected range'],
            ['key' => 'window', 'label' => 'Booking window', 'value' => $leadTime['median_days'] !== null ? $leadTime['median_days'].' days' : '—',
                'delta' => null, 'delta_dir' => null, 'sub' => $leadTime['has_data'] ? 'median, booking to check-in' : 'needs booking history'],
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
            $rows[] = ['name' => 'Group', 'occ' => $g->occupancyPct().'%', 'adr' => $this->money($g->adrMinor(), $currency), 'revpar' => $this->money($g->revparMinor(), $currency), 'group' => true];
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
                    'title' => $peak['label'].'s run hottest',
                    'body' => $peak['label'].' nights sit at '.$peak['pct'].'% across your rooms — there may be headroom for a modest premium on the strongest nights.',
                    'link' => ['label' => 'Review rates', 'url' => route('app.rates')],
                ];
            }
        }

        // 2. The real departure-day concentration — shown only when turnover genuinely clusters.
        $dep = $this->departureConcentration($ids, $range);
        if ($dep !== null) {
            $cards[] = [
                'title' => 'Turnover piles up on '.$dep['label'].'s',
                'body' => $dep['pct'].'% of departures land '.$dep['label'].'–'.$dep['next'].', so housekeeping load follows the same curve. A midweek nudge would flatten both.',
                'link' => ['label' => 'Balance the schedule', 'url' => route('app.schedule')],
            ];
        }

        // 3. Forward revenue on the books — a real, plain figure (no suggestion invented).
        if ($onTheBooks > 0) {
            $cards[] = [
                'title' => $this->money($onTheBooks, $currency).' already on the books',
                'body' => 'Room revenue confirmed for the next 90 days. Rates for the open dates are where the upside is.',
                'link' => ['label' => 'Open rates & availability', 'url' => route('app.rates')],
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

        $names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return ['label' => $names[$bestStart], 'next' => $names[($bestStart + 1) % 7], 'pct' => $pct];
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

        $year = $today->year;

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            'summer' => ['key' => 'summer', 'label' => 'Summer (Jun–Aug)', 'start' => CarbonImmutable::create($year, 6, 1), 'end' => CarbonImmutable::create($year, 9, 1)],
            'q4' => ['key' => 'q4', 'label' => 'Q4 · on the books', 'start' => CarbonImmutable::create($year, 10, 1), 'end' => CarbonImmutable::create($year + 1, 1, 1)],
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
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $active === 'ytd'],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $active === '6m'],
            ['key' => 'summer', 'label' => 'Summer (Jun–Aug)', 'active' => $active === 'summer'],
            ['key' => 'q4', 'label' => 'Q4 · on the books', 'active' => $active === 'q4'],
            ['key' => 'this-year', 'label' => 'Full year '.$year, 'active' => $active === 'this-year'],
        ];
    }

    private function currency(): string
    {
        $id = $this->currentReadIds[0] ?? null;
        $property = $id !== null ? Property::query()->whereKey($id)->first() : null;

        return (string) ($property->currency ?? 'EUR');
    }

    private function currentPropertyName(): string
    {
        $property = $this->currentProperty();

        return $property !== null ? (string) $property->name : 'No property';
    }
}
