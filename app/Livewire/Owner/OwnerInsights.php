<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\RateProposal;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Analytics\BookingCadence;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner INSIGHTS (Phase 6b · SG3) — the owner's own units' performance over the SG1 metric layer,
 * OWNER-SCOPED via {@see OwnerPortalService::units()} (never a request id; a non-linked user fails closed,
 * an owner sees ONLY their units). EVERY figure is real; a thin range shows an honest empty state; the
 * prototype's invented narrative ("Saturdays sell out first", "October is pacing 9 pts behind") is NOT
 * rendered — the ONE observation shown ("your busiest night of the week is …") is GENUINELY derived from
 * the owner's real bookings and omitted when the data is too thin to support it.
 *
 * No route-model-bound props; the range is a plain GET param so every request re-runs the owner middleware
 * (a Livewire wire-action would bypass it → isolation leak, invariant #20 / #18).
 */
#[Layout('components.layouts.owner')]
class OwnerInsights extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed when empty
        $unitIds = $units->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $currency = $this->currency($units);
        $range = $this->resolveRange();

        $metrics = PerformanceMetrics::forOwnerUnits($unitIds, $range['start'], $range['end'], $currency);
        $trend = PerformanceMetrics::monthlyTrendForOwnerUnits($unitIds, $range['start'], $range['end'], $currency);
        // The SAME window one year earlier — the REAL "vs last year" comparison. Shown per month ONLY where prior-year
        // data genuinely exists (never a fabricated delta against an empty year, #39).
        $lyTrend = PerformanceMetrics::monthlyTrendForOwnerUnits($unitIds, $range['start']->subYear(), $range['end']->subYear(), $currency);

        // Batch B — REAL booking cadence via the SHARED BookingCadence (identical to operator Insights).
        $today = CarbonImmutable::today();
        $leadTime = BookingCadence::leadTimeForOwnerUnits($unitIds, $range['start'], $range['end']);
        $weekday = BookingCadence::weekdayOccupancyForOwnerUnits($unitIds, $range['start'], $range['end']);
        $onTheBooks = BookingCadence::onTheBooksForOwnerUnits($unitIds, $today, 90, $currency);

        return view('livewire.owner.insights', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'ranges' => $this->rangeOptions($range['key']),
            'activeRange' => $range,
            'cards' => $this->cards($metrics, $currency, $leadTime, $onTheBooks),
            'trend' => $this->trend($trend, $currency),
            'occupancyByMonth' => $this->occupancyByMonth($trend, $lyTrend),
            'ratePerformance' => $this->ratePerformance($units, $range, $currency),
            'leadTime' => $leadTime,
            'weekday' => $weekday,
            'observation' => $this->busiestWeekday($unitIds, $range),
            'insights' => $this->worthALook($metrics, $leadTime, $weekday, $onTheBooks, $currency),
            // Wave C · GATE 1 — REAL pending rate proposals awaiting the owner (owner-scoped; RateProposal is
            // BelongsToOwner). The "review proposals" callout is now real, never a fabricated count (#39).
            'pendingProposals' => RateProposal::query()->where('state', RateProposal::STATE_PENDING)->count(),
        ]);
    }

    /**
     * Occupancy by month — the REAL per-month occupancy from the owner metric layer, each flagged `forward`
     * when the month starts after today (so the view can draw it as "on the books" rather than achieved).
     *
     * The "vs last year" delta (GATE 4) is the SAME month one calendar year earlier, matched by month-of-year,
     * attached ONLY where the prior-year month genuinely had occupancy — never a fabricated delta against an
     * empty year (#39). Most single-year owners simply see no delta; a second-year owner sees a real one.
     *
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $rows
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $lyRows
     * @return array{has_data: bool, has_ly: bool, months: list<array{label: string, occ_pct: int, forward: bool, ly_pct: int|null, delta_pts: int|null}>}
     */
    private function occupancyByMonth(array $rows, array $lyRows): array
    {
        $thisMonth = CarbonImmutable::today()->startOfMonth();

        // Prior-year occupancy keyed by its own 'YYYY-MM'; only months that actually had occupancy count as data.
        $lyByMonth = [];
        foreach ($lyRows as $lyRow) {
            $lyOcc = $lyRow['metrics']->occupancyPct();
            if ($lyOcc > 0) {
                $lyByMonth[$lyRow['month']] = $lyOcc;
            }
        }

        $months = [];
        $withData = 0;
        $withLy = 0;

        foreach ($rows as $row) {
            $occ = $row['metrics']->occupancyPct();
            if ($occ > 0) {
                $withData++;
            }

            // The matching prior-year month (same month-of-year, one year back).
            $lyKey = CarbonImmutable::parse($row['month'].'-01')->subYear()->format('Y-m');
            $lyPct = $lyByMonth[$lyKey] ?? null;
            if ($lyPct !== null) {
                $withLy++;
            }

            $months[] = [
                'label' => $row['label'],
                'occ_pct' => $occ,
                'forward' => CarbonImmutable::parse($row['month'].'-01')->greaterThan($thisMonth),
                'ly_pct' => $lyPct,
                'delta_pts' => $lyPct !== null ? $occ - $lyPct : null,
            ];
        }

        return ['has_data' => $withData > 0, 'has_ly' => $withLy > 0, 'months' => $months];
    }

    /**
     * Rate performance per unit — REAL occupancy + achieved ADR for each of the owner's units. The design's
     * "Δ vs last year" is OMITTED (no prior-year data → never a fabricated delta, #39).
     *
     * @param  Collection<int, Unit>  $units
     * @return list<array{name: string, occ_pct: int, adr: string}>
     */
    private function ratePerformance(Collection $units, array $range, string $currency): array
    {
        return $units->map(function (Unit $unit) use ($range, $currency): array {
            $m = PerformanceMetrics::forOwnerUnits([(string) $unit->id], $range['start'], $range['end'], $currency);

            return [
                'name' => $unit->name !== null ? (string) $unit->name : (string) $unit->code,
                'occ_pct' => $m->occupancyPct(),
                'adr' => $this->money($m->adrMinor(), $currency),
            ];
        })->all();
    }

    /**
     * "Worth a look" — REAL DERIVED observations computed from the owner's own numbers (decisions stay theirs).
     * Each is a genuine fact with an honest CTA to a real page (Rates / Calendar) — never invented advice (#39).
     * (Rate PROPOSALS are a separate real entity now, Wave C · GATE 1 — surfaced via the attention feed + the
     * pending-proposals count; these observations never fabricate one.) Included only when the data supports it.
     *
     * @param  array{has_data: bool, median_days: int|null}  $leadTime
     * @param  array{has_data: bool, weekdays: list<array{label: string, pct: int, display: string}>}  $weekday
     * @return list<array{icon: string, title: string, body: string, cta: string, href: string}>
     */
    private function worthALook(MetricSet $m, array $leadTime, array $weekday, int $onTheBooks, string $currency): array
    {
        $out = [];

        // 1. The hottest night of the week — a real fact from the day-of-week occupancy aggregate.
        if ($weekday['has_data'] && $weekday['weekdays'] !== []) {
            $days = collect($weekday['weekdays']);
            $top = $days->sortByDesc('pct')->first();
            $low = $days->sortBy('pct')->first();
            if ($top !== null && (int) $top['pct'] > 0) {
                $out[] = [
                    'icon' => '📈',
                    'title' => $top['label'].' runs hottest',
                    'body' => $top['label'].' averages '.$top['pct'].'% occupancy while '.$low['label'].' trails at '.$low['pct'].'% — worth weighing a stronger '.$top['label'].' rate against a midweek nudge.',
                    'cta' => 'Review your rates',
                    'href' => route('owner.rates'),
                ];
            }
        }

        // 2. The booking window — the real median lead time.
        if ($leadTime['has_data'] && $leadTime['median_days'] !== null) {
            $days = $leadTime['median_days'];
            $out[] = [
                'icon' => '🗓️',
                'title' => 'Guests book about '.$days.' '.($days === 1 ? 'day' : 'days').' ahead',
                'body' => 'Your median booking window is '.$days.' '.($days === 1 ? 'day' : 'days').'. Rate changes land best before that window opens for the dates you want to move.',
                'cta' => 'Open your calendar',
                'href' => route('owner.calendar'),
            ];
        }

        // 3. Forward revenue already secured — a real on-the-books figure.
        if ($onTheBooks > 0) {
            $out[] = [
                'icon' => '💶',
                'title' => $this->money($onTheBooks, $currency).' already on the books',
                'body' => 'Room revenue booked for the next 90 days. Open dates in that window are where a rate tweak still has time to work.',
                'cta' => 'Review your rates',
                'href' => route('owner.rates'),
            ];
        }

        return $out;
    }

    /**
     * @param  array{has_data: bool, median_days: int|null}  $leadTime
     * @return list<array{label: string, value: string, sub: string}>
     */
    private function cards(MetricSet $m, string $currency, array $leadTime, int $onTheBooks): array
    {
        return [
            ['label' => 'Occupancy', 'value' => $m->occupancyPct().'%', 'sub' => $m->roomNightsSold.' of '.$m->availableRoomNights.' unit-nights'],
            ['label' => 'Avg nightly rate', 'value' => $this->money($m->adrMinor(), $currency), 'sub' => 'per night sold'],
            // Batch B — REAL: room revenue already booked for the next 90 days + the real median lead time.
            ['label' => 'Next 90 days', 'value' => $this->money($onTheBooks, $currency), 'sub' => 'already on the books'],
            ['label' => 'Median lead time', 'value' => $leadTime['median_days'] !== null ? $leadTime['median_days'].' days' : '—', 'sub' => $leadTime['has_data'] ? 'booking to check-in' : 'needs booking history'],
        ];
    }

    /**
     * @param  list<array{month: string, label: string, metrics: MetricSet}>  $rows
     * @return array{enough: bool, max_minor: int, months: list<array{label: string, revenue_minor: int, revenue: string}>}
     */
    private function trend(array $rows, string $currency): array
    {
        $months = [];
        $withData = 0;
        $maxMinor = 0;

        foreach ($rows as $row) {
            $rev = $row['metrics']->roomRevenueMinor;
            if ($rev > 0 || $row['metrics']->roomNightsSold > 0) {
                $withData++;
            }
            $maxMinor = max($maxMinor, $rev);
            $months[] = ['label' => $row['label'], 'revenue_minor' => $rev, 'revenue' => $this->money($rev, $currency)];
        }

        return ['enough' => $withData >= 2, 'max_minor' => $maxMinor, 'months' => $months];
    }

    /**
     * A GENUINELY-derived observation: the weekday carrying the most room-nights across the owner's units in
     * range. Returned only when the data is strong enough (≥ 5 nights and a clear leader) — otherwise null,
     * so a thin dataset yields NO observation rather than a fabricated one.
     *
     * @param  list<string>  $unitIds
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    private function busiestWeekday(array $unitIds, array $range): ?string
    {
        if ($unitIds === []) {
            return null;
        }

        $lines = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $range['end']->toDateString())
            ->whereDate('check_out', '>', $range['start']->toDateString())
            ->get(['check_in', 'check_out']);

        $byWeekday = array_fill(0, 7, 0);
        $total = 0;

        foreach ($lines as $line) {
            $night = CarbonImmutable::parse((string) $line->check_in);
            $checkOut = CarbonImmutable::parse((string) $line->check_out);
            $lo = $night->greaterThan($range['start']) ? $night : $range['start'];
            $hi = $checkOut->lessThan($range['end']) ? $checkOut : $range['end'];

            for ($d = $lo; $d->lessThan($hi); $d = $d->addDay()) {
                $byWeekday[(int) $d->dayOfWeek]++;
                $total++;
            }
        }

        if ($total < 5) {
            return null;   // too thin to claim a pattern — omit rather than fabricate
        }

        $topDay = (int) array_keys($byWeekday, max($byWeekday))[0];
        $topCount = $byWeekday[$topDay];
        // Require a genuine leader (strictly more than an even split) before making the claim.
        if ($topCount <= (int) ceil($total / 7)) {
            return null;
        }

        $names = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        return 'Your busiest night of the week is '.$names[$topDay].'.';
    }

    /**
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $key = (string) request()->query('range', 'this-year');

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
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
            ['key' => 'this-year', 'label' => $year, 'active' => $active === 'this-year'],
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $active === 'ytd'],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $active === '6m'],
        ];
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
