<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\BookingConfirmationMail;

/**
 * G1 — the guest's own booking was confirmed. Fires on the live `reservation.confirmed`.
 *
 * ⚠️ ADDITIVE TO THE EMAIL, NEVER A REPLACEMENT. `SendGuestTransactionalEmails` still sends
 * {@see BookingConfirmationMail} on this same event and is untouched.
 *
 * ⚠️ GATE 2 refactored this onto {@see GuestReservationNotification}: the copy rule it used to hold inline is
 * now shared by all six reservation-scoped types, so they cannot drift apart. Behaviour is unchanged.
 */
class GuestBookingConfirmedNotification extends GuestReservationNotification
{
    public function eventKey(): string
    {
        return 'guest_booking_confirmed';
    }

    public function category(): string
    {
        return 'trips';
    }

    protected function templateKey(): string
    {
        return 'booking-confirmation';
    }

    protected function url(): string
    {
        return route('guest.reservation', ['reservation' => $this->reservation->getKey()], false);
    }
}
