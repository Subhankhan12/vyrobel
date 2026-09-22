<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use App\Actions\Reservations\ReverseCheckOut;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Livewire\Owner\OwnerBookingDetail;
use App\Services\AssignmentService;
use App\Services\BookingImportService;
use App\Services\GuestDeletionService;

/**
 * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-22 — A STAY THE GUEST IS PHYSICALLY IN CANNOT BE TERMINATED.
 *
 * An expected, clean rejection thrown by {@see AssignmentService::cancelReservation()} /
 * {@see AssignmentService::markNoShow()} when the reservation is `checked_in`. Extends
 * {@see BookingException} (→ RuntimeException) so every existing `catch (RuntimeException)` surfaces it as a
 * flash, never a 500 — the same posture as {@see ReservationAlreadyTerminalException}.
 *
 * ⚠️⚠️ THIS IS A DIFFERENT CONCEPT FROM #29, AND THAT IS WHY IT IS A DIFFERENT EXCEPTION AND A DIFFERENT
 * CONSTANT. #29 bounds a SECOND termination: `cancelled`/`no_show`/`checked_out` are already final, so
 * terminating them again is a REWRITE. `checked_in` is not final at all — the stay is in progress and is
 * *expected* to become `checked_out`. Refusing it is a different rule for a different reason: cancelling
 * un-arrives a guest who is standing in the room and frees a room that is occupied.
 *
 * ⚠️⚠️ DO NOT "SIMPLIFY" THIS BY ADDING `checked_in` TO {@see AssignmentService::TERMINAL_STATUSES}. That was
 * measured 2026-09-22 and it changes FOUR unrelated behaviours, none of them ruled:
 *   · {@see GuestDeletionService} reads `whereNotIn('status', TERMINAL_STATUSES)` to find a
 *     guest's ACTIVE stays — a checked-in guest would stop counting as active on the GDPR path.
 *   · {@see OwnerBookingDetail} reads it for "has an upcoming check-in".
 *   · {@see BookingImportService::modify()} would start refusing OTA modifications of a
 *     checked-in stay — which **R6 expressly permits** (the channel keeps the last word; see STATUS-LADDERS).
 *   · `ReservationLifecycleController::CANCELLABLE` is asserted to be the exact COMPLEMENT of
 *     `TERMINAL_STATUSES` over the 7-value enum; `checked_in` is now in neither, so that partition is gone.
 * The exception message would also be a lie: "already checked_in and cannot be marked cancelled" reads as
 * though the stay were finished. One concept, one constant — these are two concepts.
 *
 * ⚠️⚠️ THE REMEDY THIS NAMES IS DELIBERATELY *CHECK-OUT*, NOT "REVERSE THE CHECK-IN". Measured by three
 * independent methods 2026-09-22 (the `app/Actions/Reservations/` inventory · every write of
 * `status => 'confirmed'` · every lifecycle route): **the product has no reverse-check-in.** There is a
 * {@see ReverseCheckOut} (R3, `checked_out → checked_in`) and nothing that walks
 * `checked_in` back to `confirmed`. Naming a control that does not exist is the same defect as a fabricated
 * figure (#39) — it sends a front desk hunting for a button. So this names the exit that IS built and says
 * plainly that the other one is not. ⚠️ **Building `checked_in → confirmed` is a new ladder transition and
 * therefore a FOUNDER RULING** (the R-series), reported, not decided, by the pass that wrote this.
 */
class ReservationInProgressException extends BookingException
{
    /**
     * @param  string  $fromStatus  the reservation's real current status (always `checked_in` today)
     * @param  string  $toStatus  the termination that was attempted (`cancelled` or `no_show`)
     */
    public function __construct(
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {
        parent::__construct(self::messageFor($toStatus));
    }

    /**
     * ⚠️ THE ONE definition of this refusal's wording. {@see ReservationLifecycleController::cancel()} reads
     * it rather than hand-typing a second copy: the controller refuses first (so the operator gets a flash,
     * not an exception) and the service refuses authoritatively, and two hand-written copies of one sentence
     * is exactly how those two layers came to disagree about `checked_out` in the first place.
     */
    public static function messageFor(string $toStatus): string
    {
        return "This stay is checked in — the guest is in the room, so it cannot be marked {$toStatus}. "
            .'Check the guest out to end the stay; reversing a check-in recorded in error is not built yet.';
    }
}
