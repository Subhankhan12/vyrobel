<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Availability;
use App\Models\InventoryBlock;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Honest per-UNIT availability for the operator New Reservation screen (Wave 4 · Sub-gate 2, GUARD 1).
 *
 * ⚠️ WHY THIS EXISTS. {@see AvailabilityService} answers per ROOM TYPE and returns a COUNT; the New
 * Reservation screen must offer a specific PHYSICAL unit, and must never offer one that
 * {@see AssignmentService::createBooking()} would then reject. So this read mirrors createBooking's two
 * acceptance checks EXACTLY — minus the row locks, because it only INFORMS the UI; it guarantees nothing.
 * createBooking's `SELECT … FOR UPDATE` remains the sole no-double-booking guarantee, so a unit that goes
 * between this read and the write is still safely rejected there (caught and surfaced, never a double book):
 *   • Level A (room type): every night has a published availability row with `available() ≥ 1`
 *     (mirrors AssignmentService::assertCountsAvailable) — otherwise createBooking throws, so NONE of the
 *     room type's units are offered.
 *   • Level B (unit): the unit has NO overlapping active `reservation_unit` and NO overlapping
 *     `inventory_block` on the half-open `[check_in, check_out)` (mirrors AssignmentService::assertUnitFree)
 *     — an occupied or blocked unit is EXCLUDED entirely, never shown as free.
 *
 * A unit is additionally only BOOKABLE when its room type is PRICED for every night (a booking needs a
 * price). An available-but-unpriced room type is returned with `priced = false` so the screen can surface it
 * with an honest "set a rate first" rather than silently pricing a stay at zero.
 *
 * TENANT-scoped: every model here is BelongsToOrganization, so the reads are org-scoped by the global scope
 * (no explicit organization predicate — adding one would imply the scope were optional).
 */
class UnitAvailabilityService
{
    /** A guard on the per-night loops — no operator books a stay longer than this from one screen. Public: the New reservation
     *  wizard and its write name this limit when they refuse a longer stay (2026-10-04). */
    public const MAX_NIGHTS = 90;

    /**
     * The room types with at least one genuinely-free unit for the whole stay, each with its free units and
     * the stay's price through its primary rate plan. Room types that fail Level A, or have no free unit, are
     * omitted entirely. Returns [] for an invalid or empty range.
     *
     * @return list<array{
     *   room_type_id: string, room_type_name: string, rate_plan_id: ?string, currency: string,
     *   nights: int, price_minor: int, priced: bool,
     *   plans: list<array{id: string, name: string, price_minor: int, currency: string}>,
     *   units: list<array{id: string, code: string, name: string, sleeps: int}>
     * }>
     */
    public function availableRoomTypes(string $propertyId, string $checkIn, string $checkOut): array
    {
        $nights = $this->nights($checkIn, $checkOut);
        if ($nights === []) {
            return [];
        }

        $firstNight = $nights[0];
        $outExclusive = CarbonImmutable::parse($checkOut)->toDateString();

        $roomTypes = RoomType::query()
            ->where('property_id', $propertyId)
            ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
            ->orderBy('name')
            ->get();

        if ($roomTypes->isEmpty()) {
            return [];
        }

        $roomTypeIds = $roomTypes->map(fn (RoomType $rt): string => (string) $rt->getKey())->all();

        // Level A source: available count per (room type, night).
        $available = [];
        foreach (Availability::query()->whereIn('room_type_id', $roomTypeIds)->whereIn('date', $nights)->get() as $row) {
            $available[$row->room_type_id.'|'.CarbonImmutable::parse($row->date)->toDateString()] = $row->available();
        }

        /** @var Collection<string, Collection<int, Unit>> $unitsByRoomType */
        // BOOKABLE units only — never offer a paused / out-of-service unit for a NEW booking (it isn't in the
        // availability count either, so offering it would be an unbookable, non-reconciling option).
        $unitsByRoomType = Unit::query()->bookable()->whereIn('room_type_id', $roomTypeIds)->orderBy('code')->get()->groupBy('room_type_id');
        $unitIds = $unitsByRoomType->flatten()->map(fn (Unit $u): string => (string) $u->getKey())->all();

        $occupied = $this->occupiedUnitIds($unitIds, $firstNight, $outExclusive);
        $blocked = $this->blockedUnitIds($unitIds, $firstNight, $outExclusive);

        $result = [];

        foreach ($roomTypes as $roomType) {
            $rtId = (string) $roomType->getKey();

            if (! $this->levelAClear($rtId, $nights, $available)) {
                continue;   // room type is full or unpublished on some night — createBooking would reject
            }

            $freeUnits = [];
            foreach ($unitsByRoomType->get($rtId, collect()) as $unit) {
                $unitId = (string) $unit->getKey();
                if (isset($occupied[$unitId]) || isset($blocked[$unitId])) {
                    continue;   // Level B: physically taken — never offered
                }
                $freeUnits[] = [
                    'id' => $unitId,
                    'code' => (string) $unit->code,
                    'name' => (string) ($unit->name ?? $unit->code),
                    'sleeps' => (int) $unit->max_occupancy,   // real capacity — the group capacity meter (never fabricated)
                ];
            }

            if ($freeUnits === []) {
                continue;
            }

            $plan = $this->primaryRatePlan($roomType);
            [$priceMinor, $priced, $currency] = $this->priceOverStay($plan, $nights);

            // All REAL active + priced rate plans for this room type over the stay (the operator's selector).
            // Server-derived prices only — the client never supplies a price; an unpriced plan isn't offered.
            $plans = [];
            foreach ($roomType->ratePlans as $rp) {
                [$rpPrice, $rpPriced, $rpCurrency] = $this->priceOverStay($rp, $nights);
                if ($rpPriced && $rpCurrency === $currency) {
                    $plans[] = [
                        'id' => (string) $rp->getKey(),
                        'name' => (string) $rp->name,
                        'price_minor' => $rpPrice,
                        'currency' => $rpCurrency,
                    ];
                }
            }

            $result[] = [
                'room_type_id' => $rtId,
                'room_type_name' => (string) $roomType->name,
                'rate_plan_id' => $plan?->getKey() !== null ? (string) $plan->getKey() : null,
                'currency' => $currency,
                'nights' => count($nights),
                'price_minor' => $priceMinor,
                'priced' => $priced,
                'plans' => $plans,   // >1 → a real rate-plan selector; 1 → shown honestly; never a fabricated discount
                'units' => $freeUnits,
            ];
        }

        return $result;
    }

    /**
     * Physically-free units of a room type for [checkIn, checkOut) — LEVEL B ONLY (never gated on the Level A
     * count), for the front-desk MOVE / ASSIGN picker. A move within a room type doesn't change the count, and
     * an already-booked reservation has consumed its Level-A slot, so requiring Level A here would wrongly hide
     * physically-free units. A unit occupied by ANOTHER active reservation, or blocked, is excluded; the given
     * reservation's OWN occupancy is ignored (so its current unit isn't shown as taken and same-date moves work).
     * Best-effort for the UI — {@see AssignmentService::assignUnit} re-checks Level B under a row lock and is
     * the sole guarantee, so a unit taken between this read and the write is still safely refused there.
     *
     * @return list<array{id: string, code: string, name: string}>
     */
    public function freeUnitsForRoomType(string $propertyId, string $roomTypeId, string $checkIn, string $checkOut, ?string $ignoreReservationId = null): array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) !== 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut) !== 1) {
            return [];
        }

        $in = CarbonImmutable::parse($checkIn)->toDateString();
        $outExclusive = CarbonImmutable::parse($checkOut)->toDateString();

        // BOOKABLE units only — a move/assign target must never be a paused / out-of-service unit.
        $units = Unit::query()->bookable()->where('room_type_id', $roomTypeId)->orderBy('code')->get();
        if ($units->isEmpty()) {
            return [];
        }
        $unitIds = $units->map(fn (Unit $u): string => (string) $u->getKey())->all();

        $occupiedQuery = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $outExclusive)
            ->whereDate('check_out', '>', $in);
        if ($ignoreReservationId !== null) {
            $occupiedQuery->where('reservation_id', '!=', $ignoreReservationId);
        }
        $occupied = array_fill_keys(
            $occupiedQuery->pluck('unit_id')->map(fn ($id): string => (string) $id)->all(),
            true,
        );
        $blocked = $this->blockedUnitIds($unitIds, $in, $outExclusive);

        $free = [];
        foreach ($units as $unit) {
            $uid = (string) $unit->getKey();
            if (isset($occupied[$uid]) || isset($blocked[$uid])) {
                continue;
            }
            $free[] = ['id' => $uid, 'code' => (string) $unit->code, 'name' => (string) ($unit->name ?? $unit->code)];
        }

        return $free;
    }

    /** Level A: every night has a published availability row with available() ≥ 1. */
    private function levelAClear(string $roomTypeId, array $nights, array $available): bool
    {
        foreach ($nights as $night) {
            if (($available[$roomTypeId.'|'.$night] ?? 0) < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $unitIds
     * @return array<string, true> a set of occupied unit ids
     */
    private function occupiedUnitIds(array $unitIds, string $in, string $outExclusive): array
    {
        if ($unitIds === []) {
            return [];
        }

        $ids = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $outExclusive)
            ->whereDate('check_out', '>', $in)
            ->pluck('unit_id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        return array_fill_keys($ids, true);
    }

    /**
     * @param  list<string>  $unitIds
     * @return array<string, true> a set of blocked unit ids
     */
    private function blockedUnitIds(array $unitIds, string $in, string $outExclusive): array
    {
        if ($unitIds === []) {
            return [];
        }

        $ids = InventoryBlock::query()
            ->whereIn('unit_id', $unitIds)
            ->whereDate('start_date', '<', $outExclusive)
            ->whereDate('end_date', '>', $in)
            ->pluck('unit_id')
            ->filter()
            ->map(fn ($id): string => (string) $id)
            ->values()
            ->all();

        return array_fill_keys($ids, true);
    }

    /**
     * The stay's total price through the plan, whether EVERY night is priced, and the currency. A room type
     * with no plan, or a night with no rate row, is `priced = false` — never silently zero-priced.
     *
     * @return array{0: int, 1: bool, 2: string} [price_minor, priced, currency]
     */
    private function priceOverStay(?RatePlan $plan, array $nights): array
    {
        if ($plan === null) {
            return [0, false, 'EUR'];
        }

        $currency = (string) $plan->currency;
        $rows = RateCalendar::query()
            ->where('rate_plan_id', $plan->getKey())
            ->whereIn('date', $nights)
            ->get();

        $sum = 0;
        foreach ($rows as $row) {
            $sum += (int) $row->price_minor;
        }

        // Priced only if there is a rate row for EVERY night — a partial calendar cannot price the stay.
        return [$sum, $rows->count() === count($nights), $currency];
    }

    /**
     * ⚠️ DELEGATES — this used to be a SECOND definition of "the room type's primary rate plan", and it
     * disagreed with the model's (F-34, FIX 15). Neither branch here filtered `is_active`: the default branch
     * returned a plan taken off sale, and the fallback was saved only by the `where('is_active', true)`
     * closure on the eager-load far above — a constraint at a distance, correct today and one edit from not
     * being. There were THREE copies of this method in the codebase. There is now ONE, in the model, and the
     * project's own rule is why: two computations of one concept are how surfaces drift.
     */
    private function primaryRatePlan(RoomType $roomType): ?RatePlan
    {
        return $roomType->primaryRatePlan();
    }

    /**
     * The nights of the stay as Y-m-d strings (half-open [check_in, check_out)). Returns [] for a
     * non-positive or over-long range, or an unparseable date.
     *
     * @return list<string>
     */
    private function nights(string $checkIn, string $checkOut): array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) !== 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut) !== 1) {
            return [];
        }

        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();

        if ($end->lte($start) || $start->diffInDays($end) > self::MAX_NIGHTS) {
            return [];
        }

        $nights = [];
        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $nights[] = $date->toDateString();
        }

        return $nights;
    }
}
