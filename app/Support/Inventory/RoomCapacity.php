<?php

declare(strict_types=1);

namespace App\Support\Inventory;

use App\Models\Availability;
use App\Models\InventoryBlock;
use App\Models\ReservationUnit;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * VY-03 (audit of 2026-11-24) — ROOMS AGAINST BOOKINGS, NIGHT BY NIGHT: the ONE reading of "are there more bookings than rooms?".
 *
 * ⚠️⚠️ WHY THE STORED ROW IS NOT ENOUGH. `availability` keeps `total / sold / out_of_order / blocked`, and "rooms left" is clamped at 0
 * ({@see Availability::left()}) — correct for SELLING (you cannot sell −2 rooms), and silent about the −2. Reading the raw difference
 * instead would cry wolf: a PAUSED property stores `total = 0` on purpose (no new bookings; the stays in house run on), and a PAUSED
 * unit leaves `total` while the stay already on it remains in `sold`. Neither is an overbooking.
 *
 * So this counts PHYSICAL capacity, from the units themselves:
 *   rooms(night)    = units that are active and in service − those under a unit block that night − room-type blocks that night
 *   bookings(night) = `sold` − the stays already seated on a paused (inactive, in-service) unit — they keep that room
 *   over(night)     = max(0, bookings − rooms)
 *
 * ⚠️ READ-ONLY. It never writes `availability`; the single inventory write path stays AssignmentService. With `$lock`, the availability
 * rows are read FOR UPDATE inside the caller's transaction — the same rows a booking locks — so a guard built on this cannot race one.
 */
final class RoomCapacity
{
    /**
     * Nights in `[from, to)` on which there are MORE BOOKINGS THAN ROOMS today, with by how many. Empty = none (the usual answer).
     *
     * @return array<string, int> keyed by Y-m-d
     */
    public static function overbooked(string $roomTypeId, CarbonInterface|string $from, CarbonInterface|string|null $to): array
    {
        $rows = self::rows($roomTypeId, $from, $to, false);

        // ⚠️ THE CHEAP EXIT, and it is exact: `over` can only be positive on a night whose stored row is already negative
        //    (`total − sold − out_of_order − blocked < 0`), so a room type with none — nearly every one, nearly always — costs one query.
        if ($rows->every(fn (Availability $a): bool => $a->total - $a->sold - $a->out_of_order - $a->blocked >= 0)) {
            return [];
        }

        $over = [];
        foreach (self::nights($roomTypeId, $rows) as $night => $n) {
            if ($n['over'] > 0) {
                $over[$night] = $n['over'];
            }
        }

        return $over;
    }

    /**
     * WHAT TAKING CAPACITY AWAY WOULD DO over `[from, to)` (`to` NULL = to the end of the published calendar): the nights on which it
     * would leave more bookings than rooms THAN THERE ALREADY ARE, the worst such night's excess, and the first such night.
     *
     *  · `$unitId` set  — that unit stops being a room (out of order, or a block on it). A unit that is not a room anyway on a night
     *    (already out of order, already blocked that night) changes nothing there. A paused unit holds no new booking, but the stays
     *    already on it would lose their room.
     *  · `$unitId` NULL — a room-type block: one room fewer on every night.
     *
     * @return array{nights: int, peak: int, first_night: ?string}
     */
    public static function ifRemoved(string $roomTypeId, ?string $unitId, CarbonInterface|string $from, CarbonInterface|string|null $to, bool $lock = false): array
    {
        $rows = self::rows($roomTypeId, $from, $to, $lock);
        $impact = ['nights' => 0, 'peak' => 0, 'first_night' => null];

        if ($rows->isEmpty()) {
            return $impact;   // nothing published → nothing sold → nothing to oversell
        }

        $nights = self::nights($roomTypeId, $rows);
        $unit = $unitId === null ? null : Unit::query()->where('room_type_id', $roomTypeId)->find($unitId);

        if ($unitId !== null && ($unit === null || ! self::inService($unit))) {
            return $impact;   // not a room of this type, or already out of order: taking it away changes nothing
        }

        $first = (string) array_key_first($nights);
        $end = CarbonImmutable::parse((string) array_key_last($nights))->addDay()->toDateString();
        $blockedNights = $unit === null ? [] : self::nightsUnderBlock([(string) $unit->getKey()], null, $first, $end);
        $staysOnUnit = $unit === null || (bool) $unit->is_active ? [] : self::staysPerNight([(string) $unit->getKey()], $first, $end);

        foreach ($nights as $night => $n) {
            $rooms = $n['rooms'];
            $bookings = $n['bookings'];

            if ($unit === null) {
                $rooms--;                                            // a room-type block
            } elseif ((bool) $unit->is_active) {
                $rooms -= isset($blockedNights[$night]) ? 0 : 1;     // an active room, unless a block already took it that night
            } else {
                $bookings += $staysOnUnit[$night] ?? 0;              // a paused unit: its own stays lose their room
            }

            $over = max(0, $bookings - max(0, $rooms));   // fewer than no rooms is still no rooms: blocks beyond the units oversell nobody
            if ($over > $n['over']) {
                $impact['nights']++;
                $impact['peak'] = max($impact['peak'], $over);
                $impact['first_night'] ??= $night;
            }
        }

        return $impact;
    }

    /**
     * Rooms, bookings and the excess for every published night of `$rows`.
     *
     * @param  Collection<int, Availability>  $rows
     * @return array<string, array{rooms: int, bookings: int, over: int}>
     */
    private static function nights(string $roomTypeId, Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $dates = $rows->map(fn (Availability $a): string => CarbonImmutable::parse($a->date)->toDateString())->sort()->values();
        $first = (string) $dates->first();
        $end = CarbonImmutable::parse((string) $dates->last())->addDay()->toDateString();

        $units = Unit::query()->where('room_type_id', $roomTypeId)->get(['id', 'is_active', 'condition_status']);
        $inService = $units->filter(fn (Unit $u): bool => self::inService($u));
        $roomIds = $inService->filter(fn (Unit $u): bool => (bool) $u->is_active)->map(fn (Unit $u): string => (string) $u->getKey())->values()->all();
        $pausedIds = $inService->reject(fn (Unit $u): bool => (bool) $u->is_active)->map(fn (Unit $u): string => (string) $u->getKey())->values()->all();

        $blocked = self::nightsUnderBlock($roomIds, $roomTypeId, $first, $end);
        $seatedOnPaused = $pausedIds === [] ? [] : self::staysPerNight($pausedIds, $first, $end);

        $out = [];
        foreach ($rows as $row) {
            $night = CarbonImmutable::parse($row->date)->toDateString();
            $rooms = max(0, count($roomIds) - ($blocked[$night] ?? 0));
            $bookings = max(0, (int) $row->sold - ($seatedOnPaused[$night] ?? 0));
            $out[$night] = ['rooms' => $rooms, 'bookings' => $bookings, 'over' => max(0, $bookings - $rooms)];
        }
        ksort($out);

        return $out;
    }

    /** @return Collection<int, Availability> */
    private static function rows(string $roomTypeId, CarbonInterface|string $from, CarbonInterface|string|null $to, bool $lock): Collection
    {
        return Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', '>=', CarbonImmutable::parse($from)->toDateString())
            ->when($to !== null, fn ($q) => $q->whereDate('date', '<', CarbonImmutable::parse($to)->toDateString()))
            ->orderBy('date')                       // the same order every booking locks in → no deadlock between them
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->get();
    }

    private static function inService(Unit $unit): bool
    {
        return ! in_array((string) $unit->condition_status, Unit::UNBOOKABLE_CONDITIONS, true);
    }

    /**
     * How many of `$unitIds` are under a unit block on each night of `[first, end)`, plus (when `$roomTypeId` is given) the room-type
     * blocks that night — the same overlap rule AvailabilityService::blockedCount() applies.
     *
     * @param  list<string>  $unitIds
     * @return array<string, int>
     */
    private static function nightsUnderBlock(array $unitIds, ?string $roomTypeId, string $first, string $end): array
    {
        $blocks = InventoryBlock::query()
            ->whereDate('start_date', '<', $end)
            ->whereDate('end_date', '>', $first)
            ->where(function ($q) use ($unitIds, $roomTypeId): void {
                $q->whereIn('unit_id', $unitIds === [] ? ['-'] : $unitIds);
                if ($roomTypeId !== null) {
                    $q->orWhere(fn ($t) => $t->whereNull('unit_id')->where('room_type_id', $roomTypeId));
                }
            })
            ->get(['unit_id', 'start_date', 'end_date']);

        $perNight = [];   // night => [unit id => true] for unit blocks (a unit counts once), plus an integer of type blocks
        $typeBlocks = [];
        foreach ($blocks as $block) {
            $from = max($first, CarbonImmutable::parse($block->start_date)->toDateString());
            $to = min($end, CarbonImmutable::parse($block->end_date)->toDateString());
            for ($d = CarbonImmutable::parse($from); $d->toDateString() < $to; $d = $d->addDay()) {
                $night = $d->toDateString();
                if ($block->unit_id === null) {
                    $typeBlocks[$night] = ($typeBlocks[$night] ?? 0) + 1;
                } else {
                    $perNight[$night][(string) $block->unit_id] = true;
                }
            }
        }

        $counts = [];
        foreach ($perNight as $night => $ids) {
            $counts[$night] = count($ids);
        }
        foreach ($typeBlocks as $night => $n) {
            $counts[$night] = ($counts[$night] ?? 0) + $n;
        }

        return $counts;
    }

    /**
     * Active stays seated on `$unitIds`, per night of `[first, end)`.
     *
     * @param  list<string>  $unitIds
     * @return array<string, int>
     */
    private static function staysPerNight(array $unitIds, string $first, string $end): array
    {
        $lines = ReservationUnit::query()
            ->active()
            ->whereIn('unit_id', $unitIds)
            ->whereDate('check_in', '<', $end)
            ->whereDate('check_out', '>', $first)
            ->get(['check_in', 'check_out']);

        $counts = [];
        foreach ($lines as $line) {
            $from = max($first, CarbonImmutable::parse($line->check_in)->toDateString());
            $to = min($end, CarbonImmutable::parse($line->check_out)->toDateString());
            for ($d = CarbonImmutable::parse($from); $d->toDateString() < $to; $d = $d->addDay()) {
                $counts[$d->toDateString()] = ($counts[$d->toDateString()] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
