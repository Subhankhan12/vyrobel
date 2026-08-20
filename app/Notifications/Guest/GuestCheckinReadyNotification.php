<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\PreArrivalMail;
use App\Support\ArrivalPack;

/**
 * G2 — the guest's self check-in details are ready. Fires from the live `emails:send-pre-arrival` command,
 * which runs daily and is described as sending "the day the arrival window opens" — i.e. this is the #33
 * arrival-window moment, not a new trigger invented for the feature.
 *
 * ⚠️ ADDITIVE — {@see PreArrivalMail} still sends; the command's send site is untouched.
 *
 * ⚠️ #33 — the row is a POINTER, not a carrier. It says the details are ready and links to Arrival Info; the
 * door code and wifi stay behind the window-gated {@see ArrivalPack}, which the linked page reads.
 * A notification is pushed rather than visited, so putting a door code in one would move a secret outside the
 * gate that exists to hold it.
 */
class GuestCheckinReadyNotification extends GuestReservationNotification
{
    public function eventKey(): string
    {
        return 'guest_checkin_ready';
    }

    public function category(): string
    {
        return 'trips';
    }

    protected function templateKey(): string
    {
        return 'pre-arrival';
    }

    protected function url(): string
    {
        return route('guest.arrival', ['reservation' => $this->reservation->getKey()], false);
    }
}
