<?php

declare(strict_types=1);

namespace App\Support\Property;

use App\Models\Availability;
use App\Models\RoomType;
use Illuminate\Support\Collection;

/**
 * The ONE occupancy-for-a-property derivation (Phase 6a · multi-property) — TRUE occupancy for a night,
 * summed from the real Availability spine (total/sold/available) across the room types of the given
 * properties. Shared by the operator Dashboard and the Portfolio so the two surfaces can never disagree
 * about a property's occupancy (the anti-drift rule: one definition, proven shared).
 *
 * Keyed on the property's tenant-scoped room types, so another org's rows can never enter the sum, and
 * `available()` is the CLAMPED figure (never a negative oversell). An empty property (no room types, or
 * no availability published for the date) is honestly 0 — never a fabricated number.
 */
final class PropertyOccupancy
{
    /**
     * @param  list<string>  $propertyIds
     * @return array{total: int, sold: int, available: int, pct: int}
     */
    public static function forProperties(array $propertyIds, string $date): array
    {
        $empty = ['total' => 0, 'sold' => 0, 'available' => 0, 'pct' => 0];

        if ($propertyIds === []) {
            return $empty;
        }

        $roomTypeIds = RoomType::query()->whereIn('property_id', $propertyIds)->pluck('id')->all();
        if ($roomTypeIds === []) {
            return $empty;
        }

        /** @var Collection<int, Availability> $rows */
        $rows = Availability::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereDate('date', $date)
            ->get();

        $total = (int) $rows->sum('total');
        $sold = (int) $rows->sum('sold');
        $available = $rows->reduce(fn (int $carry, Availability $a): int => $carry + $a->available(), 0);

        return [
            'total' => $total,
            'sold' => $sold,
            'available' => $available,
            'pct' => $total > 0 ? (int) round($sold / $total * 100) : 0,
        ];
    }
}
