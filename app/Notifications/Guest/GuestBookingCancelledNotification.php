<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\CancellationMail;

/**
 * G5 — the guest's booking was cancelled. Fires on the live `reservation.cancelled`.
 *
 * ⚠️ ADDITIVE — {@see CancellationMail} still sends; that listener is untouched.
 *
 * ⚠️⚠️ THE URL IS `guest.trips`, NOT THE RESERVATION. This is the one type where the obvious destination is
 * the wrong one: a cancelled booking may no longer resolve for the guest, and a notification pointing at a
 * page that 404s is a dead affordance — at the worst possible moment. Trips always resolves and shows them
 * the cancellation in context.
 */
class GuestBookingCancelledNotification extends GuestReservationNotification
{
    public function eventKey(): string
    {
        return 'guest_booking_cancelled';
    }

    public function category(): string
    {
        return 'trips';
    }

    protected function templateKey(): string
    {
        return 'cancellation';
    }

    protected function url(): string
    {
        return route('guest.trips', [], false);
    }
}
