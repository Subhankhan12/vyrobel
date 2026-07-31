<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Availability;
use App\Models\InventoryBlock;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The availability spine. Derives, per room_type per night, the four terms behind
 * `available = total − sold − out_of_order − blocked`.
 *
 * WRITE BOUNDARY:
 *   - This service owns `total`, `out_of_order`, `blocked` (recompute) and is the ONLY code
 *     that seeds/recomputes those.
 *   - `sold` is owned by AssignmentService (Step 5), mutated inline inside its locked
 *     transaction. recompute() therefore NEVER touches `sold` (it is preserved on update,
 *     defaults to 0 on first insert). This service is a collaborator of AssignmentService,
 *     not a second inventory write path.
 *
 * Dates are half-open `[start, end)` nights, consistent with stay and inventory-block dates.
 */
class AvailabilityService
{
    /**
     * Recompute total / out_of_order / blocked for a room_type across every night in
     * `[start, end)`. `sold` is left untouched.
     */
    public function recompute(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): void
    {
        $roomType = RoomType::query()->findOrFail($roomTypeId);
        $start = CarbonImmutable::parse($start)->startOfDay();
        $end = CarbonImmutable::parse($end)->startOfDay();

        /** @var Collection<int, Unit> $units */
        $units = Unit::query()
            ->where('room_type_id', $roomType->getKey())
            ->where('is_active', true)
            ->get(['id', 'condition_status']);

        // #98 — a property-level booking PAUSE zeroes new availability: `total` drops to 0, so
        // `available = max(0, total − sold − …) = 0` refuses NEW bookings, while existing `sold` (owned by
        // AssignmentService, never touched here) keeps in-house stays running. Orthogonal to per-unit is_active
        // (owner unit pause). Fails open: no paused property ⇒ unchanged behaviour.
        $propertyPaused = Property::query()
            ->whereKey($roomType->property_id)
            ->whereNotNull('bookings_paused_at')
            ->exists();

        $total = $propertyPaused ? 0 : $units->count();

        $outOfServiceUnitIds = $units
            ->whereIn('condition_status', ['out_of_order', 'out_of_service'])
            ->pluck('id');
        $outOfOrder = $outOfServiceUnitIds->count();

        // Only in-service units can additionally be "blocked" — avoids double-counting a unit
        // that is already subtracted via out_of_order.
        $inServiceUnitIds = $units->pluck('id')->diff($outOfServiceUnitIds)->values();

        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            Availability::query()->updateOrCreate(
                ['room_type_id' => $roomType->getKey(), 'date' => $date->toDateString()],
                [
                    'total' => $total,
                    'out_of_order' => $outOfOrder,
                    'blocked' => $this->blockedCount($roomType->getKey(), $inServiceUnitIds, $date),
                ],
            );
        }
    }

    /**
     * Available count for a single night (0 if no availability row exists yet).
     */
    public function availableOn(string $roomTypeId, CarbonInterface|string $date): int
    {
        $row = Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', CarbonImmutable::parse($date)->toDateString())
            ->first();

        return $row?->available() ?? 0;
    }

    /**
     * Available count per night across `[start, end)`.
     *
     * @return array<string, int> keyed by Y-m-d
     */
    public function availabilityRange(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $start = CarbonImmutable::parse($start)->startOfDay();
        $end = CarbonImmutable::parse($end)->startOfDay();

        $rows = Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->get()
            ->keyBy(fn (Availability $a): string => CarbonImmutable::parse($a->date)->toDateString());

        $result = [];
        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $result[$key] = $rows->get($key)?->available() ?? 0;
        }

        return $result;
    }

    /**
     * Blocked in-service units on a night: distinct in-service units under a unit-level block
     * overlapping the night, plus room-type-level blocks (unit_id null) overlapping the night.
     * Overlap of block `[start_date, end_date)` with night `d` means `start_date <= d < end_date`.
     *
     * @param  Collection<int, string>  $inServiceUnitIds
     */
    private function blockedCount(string $roomTypeId, Collection $inServiceUnitIds, CarbonInterface $date): int
    {
        $d = $date->toDateString();

        $unitBlocked = 0;
        if ($inServiceUnitIds->isNotEmpty()) {
            $unitBlocked = InventoryBlock::query()
                ->whereIn('unit_id', $inServiceUnitIds->all())
                ->whereDate('start_date', '<=', $d)
                ->whereDate('end_date', '>', $d)
                ->distinct()
                ->count('unit_id');
        }

        $typeBlocked = InventoryBlock::query()
            ->whereNull('unit_id')
            ->where('room_type_id', $roomTypeId)
            ->whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>', $d)
            ->count();

        return $unitBlocked + $typeBlocked;
    }
}
