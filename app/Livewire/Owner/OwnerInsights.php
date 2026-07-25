<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
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
            'leadTime' => $leadTime,
            'weekday' => $weekday,
            'observation' => $this->busiestWeekday($unitIds, $range),
        ]);
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
