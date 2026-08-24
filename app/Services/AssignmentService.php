<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Models\Availability;
use App\Models\Group;
use App\Models\InventoryBlock;
use App\Models\OverbookingConflict;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\ReservationUnit;
use App\Models\ReservationUnitMove;
use App\Models\Unit;
use App\Models\UnitRateOverride;
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
    /**
     * THE ONE definition of "already terminal" — the statuses a reservation can no longer be terminated
     * FROM, because it already has been (invariant #29). Read by {@see terminate()} (which throws),
     * by {@see BookingImportService::cancel()} (which no-ops before ever asking, so a retrying
     * OTA webhook can never become a poison message), and asserted against
     * {@see ReservationLifecycleController::CANCELLABLE} — which is its exact
     * complement over the real 7-value enum, so the operator UI can never offer what this will refuse.
     *
     * `checked_out` is HERE deliberately: the guest demonstrably stayed. "Cancelling" a completed stay is not
     * a cancellation, it is the destruction of a record — and it was reachable via the OTA path until
     * 2026-07-17. The three callers used to hold their own copies of this rule and two of them disagreed
     * about exactly this value; it is one constant now so they cannot.
     *
     * @var list<string>
     */
    public const TERMINAL_STATUSES = ['cancelled', 'no_show', 'checked_out'];

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
     * Create a GROUP block ATOMICALLY — the group header + ONE member reservation per unit, every member
     * occupying inventory through the SAME locked core as {@see createBooking()} (Level A + Level B under
     * `SELECT … FOR UPDATE`), all in ONE transaction. **All-or-nothing:** if ANY member's unit is unavailable,
     * `occupy()` throws and the WHOLE block rolls back — no group, no member reservations, no reservation_units,
     * no `sold` left incremented.
     *
     * ⚠️ THE PHANTOM-EVENT GUARD (docs/WAVE4-INVENTORY.md, trap T6). `reservation.confirmed` events drive the
     * ARI push + availability recompute the moment {@see DomainEventRecorder} dispatches them (synchronously).
     * A naive `foreach { createBooking() }` would record+dispatch each member's event immediately AFTER that
     * member's inner commit — i.e. INSIDE this outer transaction — so a LATER member's failure would roll the
     * rows back but the listeners would already have fired (ARI push enqueued, availability recomputed) for
     * rows that no longer exist. Here every event is recorded ONLY AFTER the outer transaction commits, one per
     * member that ACTUALLY persisted — so nothing fires for a block that rolled back.
     *
     * The single-write-path invariant is why this lives on AssignmentService (it needs the private locking
     * core); the folio roll-up (master vs individual) is the caller's — {@see GroupBookingService}.
     *
     * @param  array<string, mixed>  $groupAttributes  name · occasion · property_id · billing_mode · block_code · pickup_deadline · contact
     * @param  list<array{reservation: array<string, mixed>, line: array<string, mixed>}>  $members
     */
    public function createGroupBooking(array $groupAttributes, array $members): Group
    {
        // Deterministic member ordering (unit, then room_type + check_in) so two concurrent group bookings
        // lock the shared availability rows in the SAME order — deadlock-free, the same discipline as the
        // per-line usort inside createBooking().
        usort($members, fn (array $a, array $b): int => [(string) ($a['line']['unit_id'] ?? ''), (string) $a['line']['room_type_id'], (string) $a['line']['check_in']]
            <=> [(string) ($b['line']['unit_id'] ?? ''), (string) $b['line']['room_type_id'], (string) $b['line']['check_in']]);

        /** @var array{group: Group, reservations: list<Reservation>} $result */
        $result = DB::transaction(function () use ($groupAttributes, $members): array {
            $group = Group::create($groupAttributes);

            $reservations = [];
            foreach ($members as $member) {
                $reservation = Reservation::create(array_merge([
                    'status' => 'confirmed',
                    'source' => 'group',
                    'booked_at' => now(),
                    'group_id' => $group->getKey(),
                ], $member['reservation'], [
                    'reference' => $member['reservation']['reference'] ?? $this->generateReference(),
                    'group_id' => $group->getKey(),
                ]));

                $this->occupy($reservation, $member['line']);   // throws → the whole block rolls back
                $this->recordStatusHistory($reservation, null, (string) $reservation->status);

                $reservations[] = $reservation;
            }

            return ['group' => $group, 'reservations' => $reservations];
        });

        // POST-COMMIT — the block is real. Record events ONLY now, one per persisted member (the phantom guard).
        $group = $result['group'];
        $reservations = $result['reservations'];

        $this->domainEvents->record('group.created', $group, [
            'member_count' => count($reservations),
            'billing_mode' => (string) $group->billing_mode,
        ]);
        $this->audit->record('group.created', $group, null, [
            'name' => $group->name,
            'member_count' => count($reservations),
        ]);

        foreach ($reservations as $reservation) {
            // The inventory-occupation event (a tentative HOLD occupies the unit too, so it must sync to
            // channels — hence this fires for confirmed AND tentative members; the status carries the hold type).
            $this->domainEvents->record('reservation.confirmed', $reservation, [
                'reference' => $reservation->reference,
                'group_id' => (string) $group->getKey(),
            ]);
            $this->audit->record('reservation.created', $reservation, null, [
                'reference' => $reservation->reference,
                'status' => $reservation->status,
                'group_id' => (string) $group->getKey(),
            ]);
        }

        $group->setRelation('reservations', collect($reservations)->map->refresh());

        return $group;
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
     * Refuse an OTA-sourced stay a unit that carries its own nightly price (channel-sync option 1).
     *
     * ⚠️ THE SOURCE IS THE WHOLE TEST. `reservations.source` distinguishes direct/OTA on the ONE reservations
     * table (there are no parallel per-channel tables), so "was this sold by a channel at the type rate?" is a
     * single column read — no channel join, no heuristics.
     *
     * ⚠️ ANY overridden NIGHT of the stay disqualifies the unit, not merely the first. A stay that straddles
     * an override is still a stay the OTA priced at the type rate for every one of its nights.
     */
    private function assertNotOverriddenForOtaStay(ReservationUnit $reservationUnit, string $unitId): void
    {
        if ((string) $reservationUnit->reservation->source !== 'ota') {
            return;   // direct / walk-in / corporate / group — unaffected, any unit is fair game
        }

        $overridden = UnitRateOverride::query()
            ->where('unit_id', $unitId)
            ->whereDate('date', '>=', CarbonImmutable::parse($reservationUnit->check_in)->toDateString())
            ->whereDate('date', '<', CarbonImmutable::parse($reservationUnit->check_out)->toDateString())
            ->exists();

        if ($overridden) {
            throw UnitUnavailableException::blocked($unitId);
        }
    }

    /**
     * Assign a physical unit to a line (or MOVE it to a different unit). Re-checks Level B on
     * the target unit; `sold` is unchanged (same room_type + nights). The old unit is released
     * implicitly (the same row now points to the new unit).
     */
    public function assignUnit(ReservationUnit $reservationUnit, string $unitId, ?string $effectiveDate = null): ReservationUnit
    {
        $unit = Unit::query()->findOrFail($unitId);

        if ($unit->room_type_id !== $reservationUnit->room_type_id) {
            throw new InvalidArgumentException('Unit room_type does not match the reservation_unit room_type.');
        }
        // Never MOVE a stay onto a paused / out-of-service unit (the UI never offers it, but a crafted request
        // must be refused too) — same "bookable" rule the availability read uses. Guarded here so BOTH the
        // move and the reschedule paths inherit it.
        if (! $unit->isBookable()) {
            throw UnitUnavailableException::blocked($unitId);
        }

        // D18-5 · CHANNEL-SYNC OPTION 1 — an OTA-sourced stay may never sit on a unit that carries its own
        // price. ⚠️ THIS IS A MONEY RULE WEARING AN ASSIGNMENT COSTUME. OTAs are structurally type-only
        // (AriSnapshotBuilder has no unit concept), so the channel sold the ROOM TYPE at the TYPE rate. A unit
        // with an override is priced for direct sale; seating an OTA booking on it means the property either
        // eats the difference or dishonours what the guest was sold. Only a non-overridden unit honours the
        // price the OTA actually quoted.
        //
        // ⚠️ IT GUARDS THE SELECTION, NOT THE LOCK. This runs BEFORE the transaction below and touches
        // neither lockAvailability() nor assertUnitFree() — the certified no-double-booking guarantee
        // (36666a8) is unchanged, and a DIRECT booking is deliberately unaffected: it may take any unit.
        $this->assertNotOverriddenForOtaStay($reservationUnit, $unitId);

        // ⚠️⚠️ THE MID-STAY EFFECTIVE DATE — THE FIRST NIGHT THE GUEST SLEPT IN THE NEW UNIT.
        //    Nights before it belong to the old unit's owner; that night and after belong to the new one.
        //    ⚠️ BOUNDED BY ARITHMETIC, NOT POLICY, AND REFUSED HERE RATHER THAN IN THE UI: a move cannot
        //    take effect before the guest arrived or after they left, because there are no nights there to
        //    attribute. A future date is refused for the same reason — nothing would ever apply it.
        if ($effectiveDate !== null) {
            $eff = CarbonImmutable::parse($effectiveDate)->startOfDay();
            $ci = CarbonImmutable::parse($reservationUnit->check_in)->startOfDay();
            $co = CarbonImmutable::parse($reservationUnit->check_out)->startOfDay();

            if ($eff->lessThanOrEqualTo($ci) || $eff->greaterThanOrEqualTo($co)) {
                throw new InvalidArgumentException(
                    'A mid-stay move date must fall inside the stay: the first night in the new unit is '
                    .'after check-in and before check-out.'
                );
            }

            if ($eff->greaterThan(CarbonImmutable::now()->startOfDay())) {
                throw new InvalidArgumentException('A mid-stay move date cannot be in the future.');
            }
        }

        $fromUnitId = $reservationUnit->unit_id;

        DB::transaction(function () use ($reservationUnit, $unitId, $effectiveDate, $fromUnitId): void {
            $nights = $this->nights($reservationUnit->check_in, $reservationUnit->check_out);
            $this->lockAvailability((string) $reservationUnit->room_type_id, $nights); // serialize
            $this->assertUnitFree($unitId, $reservationUnit->check_in, $reservationUnit->check_out, (string) $reservationUnit->getKey());
            $reservationUnit->update(['unit_id' => $unitId]);

            // ⚠️ The move record is written INSIDE the same transaction as the assignment, so the
            //    attribution can never read a moved line whose segment boundary is not yet recorded.
            if ($effectiveDate !== null) {
                ReservationUnitMove::create([
                    'organization_id' => $reservationUnit->organization_id,
                    'reservation_unit_id' => $reservationUnit->getKey(),
                    // ⚠️ CAPTURED BEFORE THE UPDATE. Eloquent syncs originals on save, so reading
                    //    getOriginal('unit_id') here would return the NEW unit and the first segment
                    //    would silently be attributed to the owner the guest moved TO.
                    'from_unit_id' => $fromUnitId,
                    'to_unit_id' => $unitId,
                    'effective_date' => CarbonImmutable::parse($effectiveDate)->toDateString(),
                    // ⚠️ BOTH DATES. The delta is the manipulation signal; a lone effective date cannot be
                    //    told apart from an honest one.
                    'stated_at' => CarbonImmutable::now(),
                    'stated_by_user_id' => auth()->id(),
                ]);
            }
        });

        $eventType = $fromUnitId === null ? 'reservation_unit.assigned' : 'reservation_unit.moved';
        $this->domainEvents->record($eventType, $reservationUnit, ['from_unit_id' => $fromUnitId, 'to_unit_id' => $unitId]);
        // ⚠️ This moves money between two third parties on one person's say-so, so the audit carries the
        //    stated date AND the moment it was stated, plus who stated it (recorded by the auditor).
        $this->audit->record($eventType, $reservationUnit, ['unit_id' => $fromUnitId], array_filter([
            'unit_id' => $unitId,
            'effective_date' => $effectiveDate === null ? null : CarbonImmutable::parse($effectiveDate)->toDateString(),
            'stated_at' => $effectiveDate === null ? null : CarbonImmutable::now()->toDateTimeString(),
        ], static fn ($v): bool => $v !== null));

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
     * RESCHEDULE a line to a new date range and/or unit — the operator Calendar's drag-to-reschedule. The
     * general move: it RELEASES the old nights' `sold` and CLAIMS the new nights' `sold` atomically (net-zero
     * on any night the two ranges share), re-checks Level A on the NEW nights AFTER the release (so a
     * self-overlapping move never blocks on its own occupancy) and Level B on the TARGET unit (ignoring THIS
     * line), then resyncs the reservation's aggregate dates. The lock covers the UNION of old + new nights, so
     * two concurrent moves onto the same unit/nights serialize on the availability rows and the second one's
     * assertUnitFree sees the first's committed line → exactly one wins (the no-double-booking guarantee, same
     * as createBooking). Money is NOT re-derived here (like assignUnit / extend): moving inventory is separate
     * from re-pricing the folio — the operator adjusts charges explicitly if the new dates carry a new rate.
     */
    public function reschedule(ReservationUnit $reservationUnit, CarbonInterface|string $newCheckIn, CarbonInterface|string $newCheckOut, ?string $newUnitId = null): ReservationUnit
    {
        if ($reservationUnit->status !== 'active') {
            throw new InvalidArgumentException('Only an active reservation line can be rescheduled.');
        }

        $newCi = CarbonImmutable::parse($newCheckIn)->startOfDay();
        $newCo = CarbonImmutable::parse($newCheckOut)->startOfDay();
        if ($newCo->lessThanOrEqualTo($newCi)) {
            throw new InvalidArgumentException('reschedule() requires check_out after check_in.');
        }

        $targetUnitId = $newUnitId ?? ($reservationUnit->unit_id !== null ? (string) $reservationUnit->unit_id : null);
        if ($newUnitId !== null) {
            $unit = Unit::query()->findOrFail($newUnitId);
            if ($unit->room_type_id !== $reservationUnit->room_type_id) {
                throw new InvalidArgumentException('Target unit room_type does not match the reservation_unit room_type.');
            }
            // Never RESCHEDULE onto a paused / out-of-service unit — the same bookable rule as a new booking /
            // a move (the UI only offers active rows; a crafted request is refused here).
            if (! $unit->isBookable()) {
                throw UnitUnavailableException::blocked($newUnitId);
            }
        }

        $oldCi = CarbonImmutable::parse($reservationUnit->check_in)->startOfDay();
        $oldCo = CarbonImmutable::parse($reservationUnit->check_out)->startOfDay();
        $fromUnitId = $reservationUnit->unit_id;

        DB::transaction(function () use ($reservationUnit, $oldCi, $oldCo, $newCi, $newCo, $targetUnitId): void {
            $oldNights = $this->nights($oldCi, $oldCo);
            $newNights = $this->nights($newCi, $newCo);
            $union = array_values(array_unique(array_merge($oldNights, $newNights)));
            sort($union);

            $rows = $this->lockAvailability((string) $reservationUnit->room_type_id, $union);   // serialize old ∪ new

            // Release the OLD nights first so a self-overlapping move doesn't block on its own occupancy,
            // then Level A on the NEW nights (now reflecting the release), then Level B on the target unit.
            $this->adjustSold($rows, $oldNights, -1);
            $this->assertCountsAvailable($rows, $newNights, (string) $reservationUnit->room_type_id);
            if ($targetUnitId !== null) {
                $this->assertUnitFree($targetUnitId, $newCi, $newCo, (string) $reservationUnit->getKey());
            }
            $this->adjustSold($rows, $newNights, +1);

            $reservationUnit->update([
                'unit_id' => $targetUnitId,
                'check_in' => $newCi->toDateString(),
                'check_out' => $newCo->toDateString(),
            ]);

            $this->resyncReservationDates((string) $reservationUnit->reservation_id);
        });

        $this->domainEvents->record('reservation_unit.rescheduled', $reservationUnit, [
            'from' => ['check_in' => $oldCi->toDateString(), 'check_out' => $oldCo->toDateString(), 'unit_id' => $fromUnitId],
            'to' => ['check_in' => $newCi->toDateString(), 'check_out' => $newCo->toDateString(), 'unit_id' => $targetUnitId],
        ]);
        $this->audit->record('reservation_unit.rescheduled', $reservationUnit,
            ['check_in' => $oldCi->toDateString(), 'check_out' => $oldCo->toDateString(), 'unit_id' => $fromUnitId],
            ['check_in' => $newCi->toDateString(), 'check_out' => $newCo->toDateString(), 'unit_id' => $targetUnitId]);

        return $reservationUnit->refresh();
    }

    /**
     * Recompute a reservation's aggregate [check_in, check_out) from its ACTIVE lines (min check-in, max
     * check-out) so the header stay dates stay truthful after a line move. Runs inside the caller's
     * transaction. No active lines ⇒ left unchanged (a fully-cancelled reservation keeps its record).
     */
    private function resyncReservationDates(string $reservationId): void
    {
        $minCheckIn = ReservationUnit::query()->where('reservation_id', $reservationId)->where('status', 'active')->min('check_in');
        $maxCheckOut = ReservationUnit::query()->where('reservation_id', $reservationId)->where('status', 'active')->max('check_out');

        if ($minCheckIn !== null && $maxCheckOut !== null) {
            Reservation::query()->whereKey($reservationId)->update([
                'check_in' => CarbonImmutable::parse($minCheckIn)->toDateString(),
                'check_out' => CarbonImmutable::parse($maxCheckOut)->toDateString(),
            ]);
        }
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
     * ⚠️ A UNIT-level block (unit_id set) is guarded by {@see assertUnitFree()} INSIDE the transaction —
     * the enforcement the migration + model docblocks always promised ("must not overlap an active
     * reservation_unit on the same unit; enforcement lives in AssignmentService"). It throws
     * {@see UnitUnavailableException} on an overlapping reservation (`occupied`) or an existing block
     * (`blocked`), and its row locks are held to commit, so a concurrent booking/block can't slip in.
     * Room-type-level blocks (unit_id null) have no specific unit to check and are unchanged.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function blockInventory(array $attributes): InventoryBlock
    {
        $unitId = $attributes['unit_id'] ?? null;

        $block = DB::transaction(function () use ($attributes, $unitId): InventoryBlock {
            if ($unitId !== null) {
                $this->assertUnitFree((string) $unitId, $attributes['start_date'], $attributes['end_date'], null);
            }

            return InventoryBlock::create($attributes);
        });

        $roomTypeId = $block->room_type_id ?? $block->unit?->room_type_id;

        $this->domainEvents->record('inventory_block.created', $block, [
            'room_type_id' => $roomTypeId,
            'start_date' => CarbonImmutable::parse($block->start_date)->toDateString(),
            'end_date' => CarbonImmutable::parse($block->end_date)->toDateString(),
        ]);
        $this->audit->record('inventory_block.created', $block, null, ['reason' => $block->reason]);

        return $block;
    }

    /**
     * Release (soft-delete) an inventory block through the single write path, then recompute availability
     * (via the `inventory_block.released` event → the recompute + ARI-push listeners, already wired). The
     * counterpart to {@see blockInventory()}; like it, never mutates `sold`. The event payload carries the
     * room type + range so the recompute — which runs AFTER the block is gone — restores the freed nights.
     */
    public function releaseInventoryBlock(InventoryBlock $block): void
    {
        $roomTypeId = $block->room_type_id ?? $block->unit?->room_type_id;
        $start = CarbonImmutable::parse($block->start_date)->toDateString();
        $end = CarbonImmutable::parse($block->end_date)->toDateString();

        DB::transaction(fn () => $block->delete());   // soft delete — the row survives for audit

        $this->domainEvents->record('inventory_block.released', $block, [
            'room_type_id' => $roomTypeId,
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $this->audit->record('inventory_block.released', $block, null, ['reason' => $block->reason]);
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
            // Never CREATE a booking onto a paused / out-of-service unit. The availability READ (Unit::bookable())
            // never OFFERS one, but a crafted create-POST could still name one — so the WRITE path must refuse it
            // too, the SAME `isBookable` rule the move (assignUnit) + reschedule paths already enforce. Covers both
            // createBooking() and createGroupBooking() (both funnel here). #29-style: guard once, every caller safe.
            $unit = Unit::query()->findOrFail((string) $unitId);
            if (! $unit->isBookable()) {
                throw UnitUnavailableException::blocked((string) $unitId);
            }
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

    /**
     * ⚠️ THE TERMINATION BOUND (invariant #29) — the guard lives HERE, at the single choke point.
     *
     * `terminate()` is private and BOTH public entries ({@see cancelReservation()}, {@see markNoShow()})
     * funnel through it, so one check covers every caller that exists or ever will. That is the point: until
     * 2026-07-17 the rule lived in the CALLERS, and there were two of them, and **they disagreed** — the
     * operator controller refused `checked_out` while `BookingImportService::cancel()` (the OTA/Channex
     * inbound path, i.e. a network-triggered RETRYING caller) let it through and stamped `cancelled_at` onto
     * a stay the guest had already completed. Two rules for one concept always drift.
     *
     * A SECOND TERMINATION IS A REWRITE, NOT AN APPEND — which is exactly why append-only (#7) never caught
     * it: it overwrites `cancelled_at`/`cancellation_reason` (destroying the provenance the detail screen
     * renders from those very columns), appends a meaningless `cancelled → cancelled` row to the audit
     * trail, and re-emits `reservation.cancelled` to every downstream consumer. Same blind spot, same shape,
     * as #27 bounding the SUM of refunds where #7 only bounded their edits.
     *
     * @throws ReservationAlreadyTerminalException when already terminal
     */
    private function terminate(Reservation $reservation, string $status, ?string $reason): Reservation
    {
        $fromStatus = (string) $reservation->status;

        // BEFORE the transaction: nothing to roll back, and no inventory is touched on a rejected call.
        if (in_array($fromStatus, self::TERMINAL_STATUSES, true)) {
            throw new ReservationAlreadyTerminalException($fromStatus, $status);
        }

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
        // `changed_by` → users, so the actor is the OPERATOR (web guard) or null — NEVER a signed-in guest
        // (a guest booking the public storefront while logged in must not leak their guest id here).
        $actorId = Auth::guard('web')->id();

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
