<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Models\Availability;
use App\Models\InventoryBlock;
use App\Models\OverbookingConflict;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The SINGLE write path for anything that touches inventory (convention #9). No other code
 * may insert/mutate `reservation_units` or `availability.sold` — ever.
 *
 * The no-double-booking guarantee lives entirely here because MySQL has no exclusion
 * constraint. Every path runs through one locking core inside a DB transaction:
 *
 *   BEGIN
 *     SELECT … FOR UPDATE the availability rows for (room_type_id, each night)   ← serializes
 *     Level A (counts):  available ≥ requested for every night
 *     Level B (unit):    no active reservation_unit overlaps [check_in, check_out) on the unit,
 *                        and no inventory_block overlaps          (locking reads → latest committed)
 *     INSERT reservation_unit; UPDATE availability.sold           ← same transaction
 *   COMMIT (releases locks)
 *   emit domain_events (post-commit)
 *
 * The availability FOR UPDATE lock is the mutex: two bookings for the same room_type+nights
 * fully serialize, so Level A (count exhaustion) and Level B (same physical unit) both see
 * committed state and exactly one wins.
 */
class AssignmentService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Create a booking: the reservation header + one or more inventory-occupying lines, all in
     * one transaction. Each line: room_type_id, check_in, check_out, and optionally unit_id,
     * rate_plan_id, adults, children, price_minor, currency.
     *
     * @param  array<string, mixed>  $reservationAttributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function createBooking(array $reservationAttributes, array $lines): Reservation
    {
        $reservation = DB::transaction(function () use ($reservationAttributes, $lines): Reservation {
            $reservation = Reservation::create(array_merge([
                'status' => 'confirmed',
                'booked_at' => now(),
            ], $reservationAttributes, [
                'reference' => $reservationAttributes['reference'] ?? $this->generateReference(),
            ]));

            // Deterministic lock ordering across concurrent bookings → no deadlocks.
            usort($lines, fn (array $a, array $b): int => [(string) $a['room_type_id'], (string) $a['check_in']]
                <=> [(string) $b['room_type_id'], (string) $b['check_in']]);

            foreach ($lines as $line) {
                $this->occupy($reservation, $line);
            }

            $this->recordStatusHistory($reservation, null, (string) $reservation->status);

            return $reservation;
        });

        // Post-commit (locks already released).
        $this->domainEvents->record('reservation.confirmed', $reservation, ['reference' => $reservation->reference]);
        $this->audit->record('reservation.created', $reservation, null, [
            'reference' => $reservation->reference,
            'status' => $reservation->status,
        ]);

        return $reservation->refresh();
    }

    /**
     * IMPORT path for OTA bookings (source=ota) — the asymmetric counterpart to createBooking.
     * Runs through the SAME locking core (single write path), but is accept-and-flag instead of
     * reject: for each line, inside the lock, if capacity is available it occupies normally
     * (status=active, sold++, identical to a direct booking); if inventory is genuinely
     * unavailable (sync lag oversold it) it STILL records the line — as status=overbooked, WITHOUT
     * incrementing sold (so sold never exceeds total) — and raises an `overbooking_conflict` for
     * manual resolution. An OTA booking is never rejected or silently dropped: you cannot un-book
     * it on the OTA side by refusing it here.
     *
     * @param  array<string, mixed>  $reservationAttributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function importBooking(array $reservationAttributes, array $lines): Reservation
    {
        $conflictCount = 0;

        $reservation = DB::transaction(function () use ($reservationAttributes, $lines, &$conflictCount): Reservation {
            $reservation = Reservation::create(array_merge([
                'status' => 'confirmed',
                'source' => 'ota',
                'booked_at' => now(),
            ], $reservationAttributes, [
                'reference' => $reservationAttributes['reference'] ?? $this->generateReference(),
            ]));

            // Same deterministic lock ordering as createBooking → deadlock-free under contention.
            usort($lines, fn (array $a, array $b): int => [(string) $a['room_type_id'], (string) $a['check_in']]
                <=> [(string) $b['room_type_id'], (string) $b['check_in']]);

            foreach ($lines as $line) {
                if ($this->occupyOrFlag($reservation, $line) !== null) {
                    $conflictCount++;
                }
            }

            $this->recordStatusHistory($reservation, null, (string) $reservation->status);

            return $reservation;
        });

        $this->domainEvents->record('reservation.imported', $reservation, [
            'reference' => $reservation->reference,
            'source' => $reservation->source,
            'ota_reservation_id' => $reservation->ota_reservation_id,
            'overbooking_conflicts' => $conflictCount,
        ]);
        $this->audit->record('reservation.imported', $reservation, null, [
            'reference' => $reservation->reference,
            'ota_reservation_id' => $reservation->ota_reservation_id,
            'overbooking_conflicts' => $conflictCount,
        ]);

        return $reservation->refresh();
    }

    /**
     * Re-sync an already-imported reservation to a modified set of lines (OTA modification).
     * Releases every current occupying line (frees sold for active, closes conflicts for
     * overbooked), updates the header, then occupies-or-flags the new lines — all in one
     * transaction. The reservation stays confirmed (unlike cancel). Idempotency for a redelivered
     * modification is guaranteed upstream by `webhook_events` (exactly-once per external event).
     *
     * @param  array<string, mixed>  $reservationAttributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function reimportBooking(Reservation $reservation, array $reservationAttributes, array $lines): Reservation
    {
        $conflictCount = DB::transaction(function () use ($reservation, $reservationAttributes, $lines): int {
            foreach ($reservation->reservationUnits()->whereIn('status', ['active', 'overbooked'])->get() as $existing) {
                $this->releaseLine($existing);
            }

            $reservation->update($reservationAttributes);

            usort($lines, fn (array $a, array $b): int => [(string) $a['room_type_id'], (string) $a['check_in']]
                <=> [(string) $b['room_type_id'], (string) $b['check_in']]);

            $conflicts = 0;
            foreach ($lines as $line) {
                if ($this->occupyOrFlag($reservation, $line) !== null) {
                    $conflicts++;
                }
            }

            return $conflicts;
        });

        $this->domainEvents->record('reservation.modified', $reservation, [
            'ota_reservation_id' => $reservation->ota_reservation_id,
            'overbooking_conflicts' => $conflictCount,
        ]);
        $this->audit->record('reservation.modified', $reservation, null, [
            'ota_reservation_id' => $reservation->ota_reservation_id,
            'overbooking_conflicts' => $conflictCount,
        ]);

        return $reservation->refresh();
    }

    /**
     * Assign a physical unit to a line (or MOVE it to a different unit). Re-checks Level B on
     * the target unit; `sold` is unchanged (same room_type + nights). The old unit is released
     * implicitly (the same row now points to the new unit).
     */
    public function assignUnit(ReservationUnit $reservationUnit, string $unitId): ReservationUnit
    {
        $unit = Unit::query()->findOrFail($unitId);

        if ($unit->room_type_id !== $reservationUnit->room_type_id) {
            throw new InvalidArgumentException('Unit room_type does not match the reservation_unit room_type.');
        }

        $fromUnitId = $reservationUnit->unit_id;

        DB::transaction(function () use ($reservationUnit, $unitId): void {
            $nights = $this->nights($reservationUnit->check_in, $reservationUnit->check_out);
            $this->lockAvailability((string) $reservationUnit->room_type_id, $nights); // serialize
            $this->assertUnitFree($unitId, $reservationUnit->check_in, $reservationUnit->check_out, (string) $reservationUnit->getKey());
            $reservationUnit->update(['unit_id' => $unitId]);
        });

        $eventType = $fromUnitId === null ? 'reservation_unit.assigned' : 'reservation_unit.moved';
        $this->domainEvents->record($eventType, $reservationUnit, ['from_unit_id' => $fromUnitId, 'to_unit_id' => $unitId]);
        $this->audit->record($eventType, $reservationUnit, ['unit_id' => $fromUnitId], ['unit_id' => $unitId]);

        return $reservationUnit->refresh();
    }

    /**
     * Extend a line to a later check_out. Re-checks Level A + Level B on the ADDED nights only
     * and increments `sold` for them.
     */
    public function extend(ReservationUnit $reservationUnit, CarbonInterface|string $newCheckOut): ReservationUnit
    {
        $newCheckOut = CarbonImmutable::parse($newCheckOut)->startOfDay();
        $currentCheckOut = CarbonImmutable::parse($reservationUnit->check_out)->startOfDay();

        if ($newCheckOut->lessThanOrEqualTo($currentCheckOut)) {
            throw new InvalidArgumentException('extend() requires a later check_out.');
        }

        DB::transaction(function () use ($reservationUnit, $currentCheckOut, $newCheckOut): void {
            $addedNights = $this->nights($currentCheckOut, $newCheckOut);
            $rows = $this->lockAvailability((string) $reservationUnit->room_type_id, $addedNights);
            $this->assertCountsAvailable($rows, $addedNights, (string) $reservationUnit->room_type_id);

            if ($reservationUnit->unit_id !== null) {
                $this->assertUnitFree((string) $reservationUnit->unit_id, $currentCheckOut, $newCheckOut, (string) $reservationUnit->getKey());
            }

            $reservationUnit->update(['check_out' => $newCheckOut->toDateString()]);
            $this->adjustSold($rows, $addedNights, +1);
        });

        $this->domainEvents->record('reservation_unit.extended', $reservationUnit, ['check_out' => $newCheckOut->toDateString()]);
        $this->audit->record('reservation_unit.extended', $reservationUnit, null, ['check_out' => $newCheckOut->toDateString()]);

        return $reservationUnit->refresh();
    }

    /**
     * Split a line at a date into two segments on the same unit. `sold` is unchanged — the
     * nights are already counted; the two half-open segments [in, split) and [split, out) do
     * not overlap, so each night still has exactly one occupying line.
     *
     * @return array{0: ReservationUnit, 1: ReservationUnit}
     */
    public function split(ReservationUnit $reservationUnit, CarbonInterface|string $splitDate): array
    {
        $split = CarbonImmutable::parse($splitDate)->startOfDay();
        $checkIn = CarbonImmutable::parse($reservationUnit->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservationUnit->check_out)->startOfDay();

        if ($split->lessThanOrEqualTo($checkIn) || $split->greaterThanOrEqualTo($checkOut)) {
            throw new InvalidArgumentException('split date must be strictly within (check_in, check_out).');
        }

        $second = DB::transaction(function () use ($reservationUnit, $checkIn, $split, $checkOut): ReservationUnit {
            $this->lockAvailability((string) $reservationUnit->room_type_id, $this->nights($checkIn, $checkOut));

            $reservationUnit->update(['check_out' => $split->toDateString()]);

            return ReservationUnit::create([
                'reservation_id' => $reservationUnit->reservation_id,
                'room_type_id' => $reservationUnit->room_type_id,
                'unit_id' => $reservationUnit->unit_id,
                'rate_plan_id' => $reservationUnit->rate_plan_id,
                'check_in' => $split->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'adults' => $reservationUnit->adults,
                'children' => $reservationUnit->children,
                'price_minor' => 0,
                'currency' => $reservationUnit->currency,
                'status' => 'active',
            ]);
        });

        $this->domainEvents->record('reservation_unit.split', $reservationUnit, ['second_id' => $second->getKey()]);

        return [$reservationUnit->refresh(), $second->refresh()];
    }

    /**
     * Cancel a single line: free its `sold` and mark it cancelled.
     */
    public function cancelReservationUnit(ReservationUnit $reservationUnit): ReservationUnit
    {
        if ($reservationUnit->status !== 'active') {
            return $reservationUnit;
        }

        DB::transaction(function () use ($reservationUnit): void {
            $nights = $this->nights($reservationUnit->check_in, $reservationUnit->check_out);
            $rows = $this->lockAvailability((string) $reservationUnit->room_type_id, $nights);
            $this->adjustSold($rows, $nights, -1);
            $reservationUnit->update(['status' => 'cancelled']);
        });

        $this->domainEvents->record('reservation_unit.cancelled', $reservationUnit, []);

        return $reservationUnit->refresh();
    }

    /**
     * Cancel the whole reservation: free every active line's `sold`, mark cancelled.
     */
    public function cancelReservation(Reservation $reservation, ?string $reason = null): Reservation
    {
        return $this->terminate($reservation, 'cancelled', $reason);
    }

    /**
     * No-show the reservation: same inventory effect as cancel (free `sold`), status no_show.
     */
    public function markNoShow(Reservation $reservation): Reservation
    {
        return $this->terminate($reservation, 'no_show', null);
    }

    /**
     * Create an inventory block through the single write path, then trigger an availability
     * recompute (via the domain event → listener). This never mutates `sold`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function blockInventory(array $attributes): InventoryBlock
    {
        $block = DB::transaction(fn (): InventoryBlock => InventoryBlock::create($attributes));

        $roomTypeId = $block->room_type_id ?? $block->unit?->room_type_id;

        $this->domainEvents->record('inventory_block.created', $block, [
            'room_type_id' => $roomTypeId,
            'start_date' => CarbonImmutable::parse($block->start_date)->toDateString(),
            'end_date' => CarbonImmutable::parse($block->end_date)->toDateString(),
        ]);
        $this->audit->record('inventory_block.created', $block, null, ['reason' => $block->reason]);

        return $block;
    }

    // ---- locking core -------------------------------------------------------------------

    /**
     * Occupy one line inside the caller's transaction: lock, Level A, Level B, insert, sold++.
     *
     * @param  array<string, mixed>  $line
     */
    private function occupy(Reservation $reservation, array $line): ReservationUnit
    {
        $roomTypeId = (string) $line['room_type_id'];
        $nights = $this->nights($line['check_in'], $line['check_out']);

        $rows = $this->lockAvailability($roomTypeId, $nights);
        $this->assertCountsAvailable($rows, $nights, $roomTypeId);

        $unitId = $line['unit_id'] ?? null;
        if ($unitId !== null) {
            $this->assertUnitFree((string) $unitId, $line['check_in'], $line['check_out'], null);
        }

        $reservationUnit = ReservationUnit::create([
            'reservation_id' => $reservation->getKey(),
            'room_type_id' => $roomTypeId,
            'unit_id' => $unitId,
            'rate_plan_id' => $line['rate_plan_id'] ?? null,
            'check_in' => $line['check_in'],
            'check_out' => $line['check_out'],
            'adults' => $line['adults'] ?? 1,
            'children' => $line['children'] ?? 0,
            'price_minor' => $line['price_minor'] ?? 0,
            'currency' => $line['currency'] ?? $reservation->currency,
            'status' => 'active',
        ]);

        $this->adjustSold($rows, $nights, +1);

        return $reservationUnit;
    }

    /**
     * OTA-import occupy: inside the caller's transaction, lock the nights, then EITHER seat the
     * line (every night has capacity → status=active, sold++) OR flag it (some night is full →
     * status=overbooked, NO sold change, raise an overbooking_conflict). Returns the conflict
     * when one was raised, else null. Level A only — OTA imports are type-assigned (unit_id null),
     * so there is no physical-unit (Level B) check; a specific unit is assigned later during
     * manual resolution / check-in.
     *
     * @param  array<string, mixed>  $line
     */
    private function occupyOrFlag(Reservation $reservation, array $line): ?OverbookingConflict
    {
        $roomTypeId = (string) $line['room_type_id'];
        $nights = $this->nights($line['check_in'], $line['check_out']);
        $rows = $this->lockAvailability($roomTypeId, $nights);

        $seatable = $this->everyNightAvailable($rows, $nights);

        $reservationUnit = ReservationUnit::create([
            'reservation_id' => $reservation->getKey(),
            'room_type_id' => $roomTypeId,
            'unit_id' => null,
            'rate_plan_id' => $line['rate_plan_id'] ?? null,
            'check_in' => $line['check_in'],
            'check_out' => $line['check_out'],
            'adults' => $line['adults'] ?? 1,
            'children' => $line['children'] ?? 0,
            'price_minor' => $line['price_minor'] ?? 0,
            'currency' => $line['currency'] ?? $reservation->currency,
            'status' => $seatable ? 'active' : 'overbooked',
        ]);

        if ($seatable) {
            $this->adjustSold($rows, $nights, +1);

            return null;
        }

        // Over capacity: recorded, not dropped, not counted in sold. Flag for manual resolution.
        return OverbookingConflict::create([
            'property_id' => $reservation->property_id,
            'reservation_id' => $reservation->getKey(),
            'reservation_unit_id' => $reservationUnit->getKey(),
            'room_type_id' => $roomTypeId,
            'channel_id' => $reservation->channel_id,
            'check_in' => $line['check_in'],
            'check_out' => $line['check_out'],
            'detected_at' => now(),
            'status' => OverbookingConflict::STATUS_OPEN,
        ]);
    }

    /**
     * Release one occupying line inside the caller's transaction. An `active` line frees its sold;
     * an `overbooked` line frees nothing (it never consumed sold) but resolves its open conflict.
     * Either way the line ends `cancelled`.
     */
    private function releaseLine(ReservationUnit $reservationUnit): void
    {
        if ($reservationUnit->status === 'active') {
            $nights = $this->nights($reservationUnit->check_in, $reservationUnit->check_out);
            $rows = $this->lockAvailability((string) $reservationUnit->room_type_id, $nights);
            $this->adjustSold($rows, $nights, -1);
        }

        if ($reservationUnit->status === 'overbooked') {
            OverbookingConflict::query()
                ->where('reservation_unit_id', $reservationUnit->getKey())
                ->where('status', OverbookingConflict::STATUS_OPEN)
                ->update([
                    'status' => OverbookingConflict::STATUS_RESOLVED,
                    'resolution' => 'cancelled_external',
                    'resolved_at' => now(),
                ]);
        }

        $reservationUnit->update(['status' => 'cancelled']);
    }

    /**
     * SELECT … FOR UPDATE the availability rows for these nights (ordered for deadlock safety).
     * Throws if any night has no published availability row. This lock covers both the `sold`
     * update AND any recompute run inside the same transaction.
     *
     * @param  list<string>  $nights
     * @return Collection<string, Availability>
     */
    private function lockAvailability(string $roomTypeId, array $nights): Collection
    {
        $rows = Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->whereIn('date', $nights)
            ->orderBy('date')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Availability $row): string => CarbonImmutable::parse($row->date)->toDateString());

        foreach ($nights as $night) {
            if (! $rows->has($night)) {
                throw InsufficientAvailabilityException::calendarNotPublished($roomTypeId, $night);
            }
        }

        /** @var Collection<string, Availability> $rows */
        return $rows;
    }

    /**
     * Level A: every night must have available ≥ 1.
     *
     * @param  Collection<string, Availability>  $rows
     * @param  list<string>  $nights
     */
    private function assertCountsAvailable(Collection $rows, array $nights, string $roomTypeId): void
    {
        foreach ($nights as $night) {
            $row = $rows->get($night);
            if ($row === null || $row->available() < 1) {
                throw InsufficientAvailabilityException::forNight($roomTypeId, $night);
            }
        }
    }

    /**
     * Non-throwing Level A for the whole stay: true only if EVERY night can seat one more unit.
     * A stay occupies all its nights, so a single full night makes the line unseatable (→ flagged
     * as overbooked by the import path, rather than partially seated).
     *
     * @param  Collection<string, Availability>  $rows
     * @param  list<string>  $nights
     */
    private function everyNightAvailable(Collection $rows, array $nights): bool
    {
        foreach ($nights as $night) {
            $row = $rows->get($night);
            if ($row === null || $row->available() < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Level B: no OTHER active reservation_unit overlaps [check_in, check_out) on this unit, and
     * no inventory_block overlaps. Locking reads → see the latest committed rows.
     */
    private function assertUnitFree(string $unitId, CarbonInterface|string $checkIn, CarbonInterface|string $checkOut, ?string $exceptReservationUnitId): void
    {
        $in = CarbonImmutable::parse($checkIn)->toDateString();
        $out = CarbonImmutable::parse($checkOut)->toDateString();

        $overlappingReservation = ReservationUnit::query()
            ->where('unit_id', $unitId)
            ->where('status', 'active')
            ->when($exceptReservationUnitId !== null, fn ($query) => $query->whereKeyNot($exceptReservationUnitId))
            ->whereDate('check_in', '<', $out)
            ->whereDate('check_out', '>', $in)
            ->lockForUpdate()
            ->exists();

        if ($overlappingReservation) {
            throw UnitUnavailableException::occupied($unitId);
        }

        $overlappingBlock = InventoryBlock::query()
            ->where('unit_id', $unitId)
            ->whereDate('start_date', '<', $out)
            ->whereDate('end_date', '>', $in)
            ->lockForUpdate()
            ->exists();

        if ($overlappingBlock) {
            throw UnitUnavailableException::blocked($unitId);
        }
    }

    /**
     * @param  Collection<string, Availability>  $rows
     * @param  list<string>  $nights
     */
    private function adjustSold(Collection $rows, array $nights, int $delta): void
    {
        foreach ($nights as $night) {
            $row = $rows->get($night);
            if ($row !== null) {
                $row->update(['sold' => max(0, $row->sold + $delta)]);
            }
        }
    }

    private function terminate(Reservation $reservation, string $status, ?string $reason): Reservation
    {
        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $status, $reason, $fromStatus): void {
            // releaseLine handles both cases: active → free sold; overbooked → close its conflict
            // (no sold change). Direct reservations only ever have active lines, so this is a
            // no-op superset for them — the OTA path is where overbooked lines appear.
            foreach ($reservation->reservationUnits()->whereIn('status', ['active', 'overbooked'])->get() as $reservationUnit) {
                $this->releaseLine($reservationUnit);
            }

            $reservation->update([
                'status' => $status,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            $this->recordStatusHistory($reservation, $fromStatus, $status, $reason);
        });

        $eventType = $status === 'no_show' ? 'reservation.no_show' : 'reservation.cancelled';
        $this->domainEvents->record($eventType, $reservation, ['reason' => $reason]);
        $this->audit->record($eventType, $reservation, ['status' => $fromStatus], ['status' => $status]);

        return $reservation->refresh();
    }

    private function recordStatusHistory(Reservation $reservation, ?string $from, string $to, ?string $reason = null): void
    {
        $actorId = Auth::id();

        ReservationStatusHistory::create([
            'reservation_id' => $reservation->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $actorId !== null ? (string) $actorId : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Nights in the half-open range [checkIn, checkOut) as Y-m-d strings.
     *
     * @return list<string>
     */
    private function nights(CarbonInterface|string $checkIn, CarbonInterface|string $checkOut): array
    {
        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();

        $nights = [];
        for ($date = $start; $date->lessThan($end); $date = $date->addDay()) {
            $nights[] = $date->toDateString();
        }

        return $nights;
    }

    private function generateReference(): string
    {
        do {
            $reference = 'R-'.Str::upper(Str::random(8));
        } while (Reservation::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
