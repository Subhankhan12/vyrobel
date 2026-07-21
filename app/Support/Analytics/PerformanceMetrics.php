<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Support\Folio\FolioTotals;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * THE ONE performance-metrics derivation (Phase 6b · Analytics) — occupancy, ADR, RevPAR, room revenue,
 * length-of-stay, cancellation rate and channel mix, over a date range, scoped to a property set
 * (operator) or an owner's units. Shared by operator Insights/Reports, the Portfolio comparison, and owner
 * Reports/Insights so every surface AGREES (the anti-drift rule; same family as {@see \App\Support\Owner\OwnerOccupancy}
 * / {@see FolioTotals}). Every figure is REAL — derived from real `reservation_units` /
 * `reservations` / `availability`; nothing is fabricated, and a range with no activity returns an honest
 * zero {@see MetricSet::isEmpty()}, never a fake curve.
 *
 * BASIS — "stay-night" attribution over the half-open range [start, end):
 *   • room-nights sold (RN) = Σ, over ACTIVE `reservation_units` overlapping the range, of the nights that
 *     fall INSIDE the range (this reconciles to the certified `availability.sold` spine by construction —
 *     the single write path keeps them in step).
 *   • room revenue (RR)     = Σ, per line, of `price_minor` PRO-RATED by (in-range nights ÷ stay nights) —
 *     so a fully-in-range stay contributes its exact folio room charge, and a boundary-straddling stay only
 *     its in-range portion. `price_minor` IS the room-charge snapshot FolioService posts, so RR reconciles
 *     to the folio room line.
 *   • available room-nights (AN):
 *       – PROPERTY scope: Σ `availability.total` over the range for the properties' room types (the
 *         certified capacity spine).
 *       – OWNER scope: (owner unit count) × (days in range) — the UNIT-level denominator {@see OwnerOccupancy}
 *         uses, because `availability` is per room type and a room type is SHARED across owners' units.
 *   • ADR = RR ÷ RN · RevPAR = RR ÷ AN · occupancy = RN ÷ AN  → RevPAR = ADR × occupancy exactly.
 */
final class PerformanceMetrics
{
    /**
     * Operator scope — one or more properties (a single property, or the Phase-6a all-properties roll-up).
     *
     * @param  list<string>  $propertyIds
     */
    public static function forProperties(array $propertyIds, CarbonImmutable $start, CarbonImmutable $end, string $currency): MetricSet
    {
        if ($propertyIds === []) {
            return self::empty($currency);
        }

        $roomTypeIds = RoomType::query()->whereIn('property_id', $propertyIds)->pluck('id')
            ->map(fn ($id): string => (string) $id)->all();

        if ($roomTypeIds === []) {
            return self::empty($currency);
        }

        $availableRoomNights = (int) Availability::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->sum('total');

        $lines = self::overlappingLines(
            ReservationUnit::query()->whereIn('room_type_id', $roomTypeIds),
            $start,
            $end,
        );

        $counts = self::arrivalCounts(
            Reservation::query()->whereIn('property_id', $propertyIds),
            $start,
            $end,
        );

        return self::assemble($currency, $availableRoomNights, $lines, $counts, $start, $end);
    }

    /**
     * A REAL month-by-month trend across [start, end) for a property scope — one {@see MetricSet} per
     * calendar month the range touches (capped at 13 to bound work). Every month is real data; a month with
     * no activity is an honest zero, NEVER a fabricated point — the caller shows a "not enough data yet"
     * state when too few months carry activity, rather than drawing a meaningless 1-point line.
     *
     * @param  list<string>  $propertyIds
     * @return list<array{month: string, label: string, metrics: MetricSet}>
     */
    public static function monthlyTrendForProperties(array $propertyIds, CarbonImmutable $start, CarbonImmutable $end, string $currency): array
    {
        $trend = [];
        $cursor = $start->startOfMonth();
        $guard = 0;

        while ($cursor->lessThan($end) && $guard < 13) {
            $bucketStart = $cursor->greaterThan($start) ? $cursor : $start;
            $nextMonth = $cursor->addMonth();
            $bucketEnd = $nextMonth->lessThan($end) ? $nextMonth : $end;

            $trend[] = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
                'metrics' => self::forProperties($propertyIds, $bucketStart, $bucketEnd, $currency),
            ];

            $cursor = $nextMonth;
            $guard++;
        }

        return $trend;
    }

    /**
     * Owner scope — the owner's OWN units only (owner-scoped upstream; never a request id). Available
     * nights use the unit-level denominator so a non-owned unit in the same room type is excluded.
     *
     * @param  list<string>  $unitIds
     */
    public static function forOwnerUnits(array $unitIds, CarbonImmutable $start, CarbonImmutable $end, string $currency): MetricSet
    {
        if ($unitIds === []) {
            return self::empty($currency);
        }

        $days = max(0, (int) $start->diffInDays($end));
        $availableRoomNights = count($unitIds) * $days;

        $lines = self::overlappingLines(
            ReservationUnit::query()->whereIn('unit_id', $unitIds),
            $start,
            $end,
        );

        // Bookings/cancellations for the owner = the reservations that touch their units, by arrival.
        $reservationIds = $lines->pluck('reservation_id')->filter()->unique()->values()->all();
        $counts = $reservationIds === []
            ? ['bookings' => 0, 'cancellations' => 0]
            : self::arrivalCounts(Reservation::query()->whereIn('id', $reservationIds), $start, $end);

        return self::assemble($currency, $availableRoomNights, $lines, $counts, $start, $end);
    }

    /**
     * A REAL month-by-month trend for an owner's units (mirrors {@see monthlyTrendForProperties}) — one
     * {@see MetricSet} per calendar month, every month real, an empty month an honest zero.
     *
     * @param  list<string>  $unitIds
     * @return list<array{month: string, label: string, metrics: MetricSet}>
     */
    public static function monthlyTrendForOwnerUnits(array $unitIds, CarbonImmutable $start, CarbonImmutable $end, string $currency): array
    {
        $trend = [];
        $cursor = $start->startOfMonth();
        $guard = 0;

        while ($cursor->lessThan($end) && $guard < 13) {
            $bucketStart = $cursor->greaterThan($start) ? $cursor : $start;
            $nextMonth = $cursor->addMonth();
            $bucketEnd = $nextMonth->lessThan($end) ? $nextMonth : $end;

            $trend[] = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
                'metrics' => self::forOwnerUnits($unitIds, $bucketStart, $bucketEnd, $currency),
            ];

            $cursor = $nextMonth;
            $guard++;
        }

        return $trend;
    }

    /**
     * Active reservation_unit lines overlapping [start, end), with their reservation's source/status for
     * the channel mix. Half-open overlap: check_in < end AND check_out > start.
     *
     * @param  Builder<ReservationUnit>  $base
     * @return Collection<int, ReservationUnit>
     */
    private static function overlappingLines(Builder $base, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $base
            ->where('status', 'active')
            ->whereDate('check_in', '<', $end->toDateString())
            ->whereDate('check_out', '>', $start->toDateString())
            ->with('reservation:id,source,status')
            ->get(['id', 'reservation_id', 'unit_id', 'room_type_id', 'check_in', 'check_out', 'price_minor', 'currency']);
    }

    /**
     * @param  Collection<int, ReservationUnit>  $lines
     * @param  array{bookings: int, cancellations: int}  $counts
     */
    private static function assemble(string $currency, int $availableRoomNights, Collection $lines, array $counts, CarbonImmutable $start, CarbonImmutable $end): MetricSet
    {
        $roomNights = 0;
        $roomRevenue = 0;
        $stays = 0;
        $guestNights = 0;
        $sourceNights = [];
        $multiCurrency = false;

        foreach ($lines as $line) {
            $checkIn = CarbonImmutable::parse((string) $line->check_in);
            $checkOut = CarbonImmutable::parse((string) $line->check_out);
            $stayNights = (int) $checkIn->diffInDays($checkOut);
            if ($stayNights <= 0) {
                continue;
            }

            $lo = $checkIn->greaterThan($start) ? $checkIn : $start;
            $hi = $checkOut->lessThan($end) ? $checkOut : $end;
            $inNights = max(0, (int) $lo->diffInDays($hi));
            if ($inNights === 0) {
                continue;
            }

            $roomNights += $inNights;
            $stays++;
            $guestNights += $stayNights;

            $source = (string) ($line->reservation->source ?? 'direct');
            $sourceNights[$source] = ($sourceNights[$source] ?? 0) + $inNights;

            // Money only sums within the scope currency (never a cross-currency sum, #17).
            if ((string) $line->currency === $currency) {
                $roomRevenue += (int) round((int) $line->price_minor * $inNights / $stayNights);
            } elseif ((int) $line->price_minor !== 0) {
                $multiCurrency = true;
            }
        }

        ksort($sourceNights);

        return new MetricSet(
            currency: $currency,
            roomNightsSold: $roomNights,
            availableRoomNights: $availableRoomNights,
            roomRevenueMinor: $roomRevenue,
            bookings: $counts['bookings'],
            cancellations: $counts['cancellations'],
            stays: $stays,
            guestNights: $guestNights,
            sourceNights: $sourceNights,
            multiCurrency: $multiCurrency,
        );
    }

    /**
     * Reservations arriving (check_in) inside [start, end), split active vs cancelled/no-show.
     *
     * @param  Builder<Reservation>  $base
     * @return array{bookings: int, cancellations: int}
     */
    private static function arrivalCounts(Builder $base, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $arriving = (clone $base)
            ->whereDate('check_in', '>=', $start->toDateString())
            ->whereDate('check_in', '<', $end->toDateString());

        return [
            'bookings' => (clone $arriving)->whereNotIn('status', ['cancelled', 'no_show'])->count(),
            'cancellations' => (clone $arriving)->whereIn('status', ['cancelled', 'no_show'])->count(),
        ];
    }

    private static function empty(string $currency): MetricSet
    {
        return new MetricSet($currency, 0, 0, 0, 0, 0, 0, 0, [], false);
    }
}
