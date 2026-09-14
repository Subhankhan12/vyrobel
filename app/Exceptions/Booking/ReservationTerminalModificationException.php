<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use App\Services\AssignmentService;
use App\Services\BookingImportService;

/**
 * An expected, clean rejection thrown by {@see AssignmentService::reimportBooking()} — the MODIFICATION
 * BOUND: a reservation that is already terminal cannot be re-synced back to life by an inbound channel.
 *
 * THE DEFECT THIS DEFENDS AGAINST (F-29, found by the fix programme 2026-09-13):
 * {@see BookingImportService::build()} sets `'status' => 'confirmed'` UNCONDITIONALLY, and `modify()` handed
 * that straight to `reimportBooking()`, which did a raw `$reservation->update($attributes)`. An OTA
 * modification of a `cancelled`, `no_show` or `checked_out` booking therefore REWROTE IT BACK TO
 * `confirmed` — and did more than change a status: `reimportBooking()` releases every occupying line and
 * re-occupies the new ones, so a dead booking silently RE-TOOK INVENTORY, and `total_minor` (read as money
 * on the confirmation email, the owner booking detail, the guests CRM and the reservations list's
 * SUM(total_minor)) was rewritten with whatever the channel last sent.
 *
 * THE SAME SHAPE AS #29, ONE METHOD OVER. {@see ReservationAlreadyTerminalException} bounds a second
 * TERMINATION; this bounds a REVIVAL. Both are rewrites of a settled record, and neither is caught by
 * append-only (#7) — which is exactly why #27 (the SUM of refunds) and #29 (the second termination) had to
 * be written separately too.
 *
 * ⚠️ THE TWO-LAYER RULE, COPIED FROM THE SIBLING, NOT INVENTED. `BookingImportService::cancel()` carries a
 * comment stating the architecture in the author's own words: **"Idempotent here; authoritative there."**
 * The inbound webhook path NO-OPS on a terminal booking (a retrying delivery must never become a poison
 * message); the service THROWS, because it is the authoritative writer and a non-webhook caller deserves a
 * loud refusal. This exception is the "there".
 *
 * Extends {@see BookingException} (→ RuntimeException) so the existing `catch (RuntimeException)` in the
 * operator controllers surfaces it as a flash, exactly like the no-double-booking rejections — never a 500.
 */
class ReservationTerminalModificationException extends BookingException
{
    /**
     * @param  string  $fromStatus  the reservation's real current status (already terminal)
     */
    public function __construct(
        public readonly string $fromStatus,
    ) {
        parent::__construct(
            "This reservation is {$fromStatus} and cannot be modified by a channel sync."
        );
    }
}
