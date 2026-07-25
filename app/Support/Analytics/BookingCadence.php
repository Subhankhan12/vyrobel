<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * THE ONE booking-cadence derivation (Batch B · GATE 3) — lead time, day-of-week occupancy and on-the-books
 * revenue, shared by operator Insights (property scope) AND owner Insights (owner-unit scope) so the two
 * pages compute them identically and cannot drift. Same anti-drift discipline as {@see PerformanceMetrics}
 * (whose scope machinery this mirrors: property → RoomType → Availability spine + reservation_units by
 * room_type_id; owner → the owner's OWN unit ids, denominator = unit count).
 *
 * EVERY figure is a REAL, deterministic aggregate from real rows — NEVER a fabricated trend or benchmark
 * (#39):
 *   • lead time     = check_in − created_at, per real reservation ARRIVING in range (median + a 4-bucket
 *                     distribution). Empty → an honest "not enough data yet", never an invented curve.
 *   • DOW occupancy = room-nights sold on each weekday ÷ available room-nights on that weekday (the real
 *                     availability spine for a property scope, the unit count for an owner scope).
 *   • on the books  = the real room revenue ALREADY booked for stays in the forward window.
 *
 * Owner isolation (#28) lives in the caller's unit ids (owner-scoped upstream, never a request id); tenant
 * scope rides the models' global scopes. This class only reads — it writes nothing.
 */
final class BookingCadence
{
    /** The lead-time buckets (inclusive day ranges) — the wireframe's "When guests book" distribution. */
    private const BUCKETS = [
        ['label' => 'Under a week', 'min' => 0, 'max' => 6],
        ['label' => '1–4 weeks', 'min' => 7, 'max' => 27],
        ['label' => '1–3 months', 'min' => 28, 'max' => 89],
        ['label' => '3+ months', 'min' => 90, 'max' => PHP_INT_MAX],
    ];

    // ── LEAD TIME ────────────────────────────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $propertyIds
     * @return array{has_data: bool, count: int, median_days: int|null, buckets: list<array{label: string, count: int, pct: int}>}
     */
    public static function leadTimeForProperties(array $propertyIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($propertyIds === []) {
            return self::emptyLeadTime();
        }

        return self::leadTime(Reservation::query()->whereIn('property_id', $propertyIds), $start, $end);
    }

    /**
     * @param  list<string>  $unitIds  the owner's OWN unit ids
     * @return array{has_data: bool, count: int, median_days: int|null, buckets: list<array{label: string, count: int, pct: int}>}
     */
    public static function leadTimeForOwnerUnits(array $unitIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($unitIds === []) {
            return self::emptyLeadTime();
        }

        $reservations = Reservation::query()->whereIn(
            'id',
            ReservationUnit::query()->whereIn('unit_id', $unitIds)->select('reservation_id'),
        );

        return self::leadTime($reservations, $start, $end);
    }

    /**
     * @param  Builder<Reservation>  $reservations
     * @return array{has_data: bool, count: int, median_days: int|null, buckets: list<array{label: string, count: int, pct: int}>}
     */
    private static function leadTime(Builder $reservations, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $reservations
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereDate('check_in', '>=', $start->toDateString())
            ->whereDate('check_in', '<', $end->toDateString())
            ->get(['created_at', 'check_in']);

        /** @var list<int> $leads */
        $leads = [];
        foreach ($rows as $row) {
            if ($row->created_at === null) {
                continue;
            }
            $created = CarbonImmutable::parse((string) $row->created_at)->startOfDay();
            $checkIn = CarbonImmutable::parse((string) $row->check_in)->startOfDay();
            // Integer whole-day lead via timestamps — robust across Carbon's diff-sign changes.
            $leads[] = max(0, intdiv($checkIn->getTimestamp() - $created->getTimestamp(), 86400));
        }

        sort($leads);
        $count = count($leads);

        $buckets = [];
        foreach (self::BUCKETS as $def) {
            $c = 0;
            foreach ($leads as $lead) {
                if ($lead >= $def['min'] && $lead <= $def['max']) {
                    $c++;
                }
            }
            $buckets[] = ['label' => $def['label'], 'count' => $c, 'pct' => $count > 0 ? (int) round($c / $count * 100) : 0];
        }

        $median = null;
        if ($count > 0) {
            $mid = intdiv($count, 2);
            $median = $count % 2 === 1 ? $leads[$mid] : intdiv($leads[$mid - 1] + $leads[$mid], 2);
        }

        return ['has_data' => $count > 0, 'count' => $count, 'median_days' => $median, 'buckets' => $buckets];
    }

    // ── DAY-OF-WEEK OCCUPANCY ────────────────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $propertyIds
     * @return array{has_data: bool, weekdays: list<array{label: string, pct: int, display: string}>}
     */
    public static function weekdayOccupancyForProperties(array $propertyIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($propertyIds === []) {
            return self::emptyWeekdays();
        }

        $roomTypeIds = RoomType::query()->whereIn('property_id', $propertyIds)->pluck('id')
            ->map(fn ($id): string => (string) $id)->all();
        if ($roomTypeIds === []) {
            return self::emptyWeekdays();
        }

        $lines = self::activeLines(ReservationUnit::query()->whereIn('room_type_id', $roomTypeIds), $start, $end);

        // Denominator = the REAL availability spine (accounts for out-of-order / blocked), grouped by weekday.
        $den = array_fill(0, 7, 0);
        $availability = Availability::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get(['date', 'total']);
        foreach ($availability as $row) {
            $den[(int) CarbonImmutable::parse((string) $row->date)->dayOfWeek] += (int) $row->total;
        }

        return self::weekdayFrom($lines, $den, $start, $end);
    }

    /**
     * @param  list<string>  $unitIds  the owner's OWN unit ids
     * @return array{has_data: bool, weekdays: list<array{label: string, pct: int, display: string}>}
     */
    public static function weekdayOccupancyForOwnerUnits(array $unitIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($unitIds === []) {
            return self::emptyWeekdays();
        }

        $lines = self::activeLines(ReservationUnit::query()->whereIn('unit_id', $unitIds), $start, $end);

        // Denominator = unit count per date (the OwnerOccupancy rule: 1 available room-night per unit per day).
        $unitCount = count($unitIds);
        $den = array_fill(0, 7, 0);
        for ($d = $start; $d->lessThan($end); $d = $d->addDay()) {
            $den[(int) $d->dayOfWeek] += $unitCount;
        }

        return self::weekdayFrom($lines, $den, $start, $end);
    }

    /**
     * @param  Builder<ReservationUnit>  $query
     * @return Collection<int, ReservationUnit>
     */
    private static function activeLines(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $query
            ->where('status', 'active')
            ->whereDate('check_in', '<', $end->toDateString())
            ->whereDate('check_out', '>', $start->toDateString())
            ->get(['check_in', 'check_out']);
    }

    /**
     * @param  Collection<int, ReservationUnit>  $lines
     * @param  array<int, int>  $den  available room-nights per weekday (0 = Sunday … 6 = Saturday)
     * @return array{has_data: bool, weekdays: list<array{label: string, pct: int, display: string}>}
     */
    private static function weekdayFrom(Collection $lines, array $den, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $num = array_fill(0, 7, 0);
        foreach ($lines as $line) {
            $checkIn = CarbonImmutable::parse((string) $line->check_in);
            $checkOut = CarbonImmutable::parse((string) $line->check_out);
            $lo = $checkIn->greaterThan($start) ? $checkIn : $start;
            $hi = $checkOut->lessThan($end) ? $checkOut : $end;
            for ($d = $lo; $d->lessThan($hi); $d = $d->addDay()) {
                $num[(int) $d->dayOfWeek]++;
            }
        }

        // Mon → Sun display order (Carbon dayOfWeek: 0 = Sun … 6 = Sat).
        $order = [1, 2, 3, 4, 5, 6, 0];
        $labels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

        $weekdays = [];
        $any = false;
        foreach ($order as $wd) {
            $pct = $den[$wd] > 0 ? (int) round($num[$wd] / $den[$wd] * 100) : 0;
            if ($num[$wd] > 0) {
                $any = true;
            }
            $weekdays[] = ['label' => $labels[$wd], 'pct' => $pct, 'display' => $pct.'%'];
        }

        return ['has_data' => $any, 'weekdays' => $weekdays];
    }

    // ── ON THE BOOKS (forward-looking booked revenue) ────────────────────────────────────────────────

    /**
     * Real room revenue ALREADY on the books for stays in [from, from + days) — reuses {@see PerformanceMetrics}
     * over a forward range, so it reconciles with the rest of the metric layer.
     *
     * @param  list<string>  $propertyIds
     */
    public static function onTheBooksForProperties(array $propertyIds, CarbonImmutable $from, int $days, string $currency): int
    {
        return PerformanceMetrics::forProperties($propertyIds, $from, $from->addDays($days), $currency)->roomRevenueMinor;
    }

    /**
     * @param  list<string>  $unitIds  the owner's OWN unit ids
     */
    public static function onTheBooksForOwnerUnits(array $unitIds, CarbonImmutable $from, int $days, string $currency): int
    {
        return PerformanceMetrics::forOwnerUnits($unitIds, $from, $from->addDays($days), $currency)->roomRevenueMinor;
    }

    /**
     * @return array{has_data: bool, count: int, median_days: int|null, buckets: list<array{label: string, count: int, pct: int}>}
     */
    private static function emptyLeadTime(): array
    {
        return [
            'has_data' => false,
            'count' => 0,
            'median_days' => null,
            'buckets' => array_map(fn (array $d): array => ['label' => $d['label'], 'count' => 0, 'pct' => 0], self::BUCKETS),
        ];
    }

    /**
     * @return array{has_data: bool, weekdays: list<array{label: string, pct: int, display: string}>}
     */
    private static function emptyWeekdays(): array
    {
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return [
            'has_data' => false,
            'weekdays' => array_map(fn (string $l): array => ['label' => $l, 'pct' => 0, 'display' => '0%'], $labels),
        ];
    }
}
