<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Services\AssignmentService;
use App\Support\Reservations\ReservationStatusLabel;

/**
 * An expected, clean rejection thrown by {@see AssignmentService::cancelReservation()} /
 * {@see AssignmentService::markNoShow()} — the TERMINATION BOUND: a reservation that is already terminal
 * cannot be terminated again.
 *
 * Extends {@see BookingException} (→ RuntimeException) so the existing `catch (RuntimeException)` in
 * {@see ReservationLifecycleController} surfaces it as a flash, exactly like
 * the no-double-booking rejections — never a 500.
 *
 * THE INVARIANT THIS DEFENDS: a terminal state is TERMINAL. Terminating twice is not an append, it is a
 * REWRITE — it overwrites `cancelled_at` and `cancellation_reason` (destroying the real provenance the
 * detail screen renders), appends a meaningless `cancelled → cancelled` row to an append-only trail, and
 * emits a duplicate `reservation.cancelled` domain event to every downstream consumer.
 *
 * Deliberately distinct from invariant #7 (append-only), which was never violated: every row a second
 * termination wrote was a legitimate append. **#7 bounds EDITS; it never bounded a SECOND TERMINATION** —
 * the same blind spot, in the same shape, as #27 bounding the SUM of refunds where #7 only bounded their
 * edits. (Proven on real demo data 2026-07-17: a cancellation reading "Guest called — family emergency" was
 * silently replaced by "oops duplicate webhook", with a junk history row appended.)
 */
class ReservationAlreadyTerminalException extends BookingException
{
    /**
     * @param  string  $fromStatus  the reservation's real current status (already terminal)
     * @param  string  $toStatus  the termination that was attempted
     */
    public function __construct(
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {
        parent::__construct(__('php/exceptions/booking/reservation_already_terminal_exception.message', [
            'from_status' => ReservationStatusLabel::for($fromStatus),
            'to_status' => ReservationStatusLabel::for($toStatus),
        ]));
    }
}
