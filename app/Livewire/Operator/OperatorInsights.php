<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Support\Analytics\BookingCadence;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator INSIGHTS (Phase 6b · SG2) — the performance dashboard over the SG1 metric layer, scoped by the
 * Phase-6a property context (the selected property, or the all-properties roll-up via `currentReadIds`) and
 * a real date range. EVERY figure is real/derived from {@see PerformanceMetrics}; a thin/empty range shows an
 * honest "not enough data yet" state, never a fabricated curve, and the prototype's invented multi-year
 * history + generated narrative are NOT rendered. Batch B (GATE 3) lit up the REAL booking cadence via the
 * SHARED {@see BookingCadence} (identical to owner Insights, anti-drift): the median lead time (was "Soon"),
 * the "When guests book" lead-time distribution, the "Nights that fill first" day-of-week occupancy, and the
 * "Next 90 days" on-the-books revenue — every one a real aggregate, an honest empty state when history is
 * thin. The prototype's "vs last year" deltas + rate-proposal narrative stay refused (no prior-year data
 * without the demo seed; no pricing engine — #39).
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
            'cards' => $this->cards($metrics, $currency),
            'cadenceCards' => [
                // The real median lead time + the real forward-booked room revenue (replaces the "Soon" tiles).
                ['label' => 'Booking window', 'value' => $leadTime['median_days'] !== null ? $leadTime['median_days'].' days' : '—', 'sub' => $leadTime['has_data'] ? 'median, booking to check-in' : 'needs booking history'],
                ['label' => 'Next 90 days', 'value' => $this->money($onTheBooks, $currency), 'sub' => 'room revenue on the books'],
            ],
            'channelMix' => $this->channelMix($metrics),
            'trend' => $this->trend($trend, $currency),
            'leadTime' => $leadTime,
            'weekday' => $weekday,
            'multiCurrency' => $metrics->multiCurrency,
        ]);
    }

    /**
     * The metric cards — all REAL, reconciling to the SG1 layer / FolioTotals.
     *
     * @return list<array{key: string, label: string, value: string, sub: string}>
     */
    private function cards(MetricSet $m, string $currency): array
    {
        return [
            ['key' => 'occupancy', 'label' => 'Occupancy', 'value' => $m->occupancyPct().'%', 'sub' => $m->roomNightsSold.' of '.$m->availableRoomNights.' room-nights'],
            ['key' => 'adr', 'label' => 'ADR', 'value' => $this->money($m->adrMinor(), $currency), 'sub' => 'per room-night sold'],
            ['key' => 'revpar', 'label' => 'RevPAR', 'value' => $this->money($m->revparMinor(), $currency), 'sub' => 'per available room-night'],
            ['key' => 'revenue', 'label' => 'Room revenue', 'value' => $this->money($m->roomRevenueMinor, $currency), 'sub' => $m->bookings.' '.Str::plural('arrival', $m->bookings).' in range'],
            ['key' => 'los', 'label' => 'Avg length of stay', 'value' => $m->averageLengthOfStay() > 0 ? $m->averageLengthOfStay().' nights' : '—', 'sub' => $m->stays.' '.Str::plural('stay', $m->stays)],
            ['key' => 'cancellations', 'label' => 'Cancellation rate', 'value' => ($m->bookings + $m->cancellations) > 0 ? $m->cancellationRatePct().'%' : '—', 'sub' => $m->cancellations.' cancelled / no-show'],
        ];
    }

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
     * The selected date range. Default "this year" (the full calendar year) so the demo's real data is in
     * view; year-to-date and last-6-months are honest rolling windows.
     *
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
