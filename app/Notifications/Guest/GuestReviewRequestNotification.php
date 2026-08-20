<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\ReviewRequestMail;

/**
 * G7 — an invitation to review a completed stay. Fires on the live `reservation.checked_out`.
 *
 * ⚠️ ADDITIVE — {@see ReviewRequestMail} still sends; that listener is untouched.
 *
 * ⚠️ The url is the guest's own review page for THIS stay, which is tied to a real completed reservation
 * (#76 — a review only ever exists against a stay that happened). Nothing is fabricated by asking.
 */
class GuestReviewRequestNotification extends GuestReservationNotification
{
    public function eventKey(): string
    {
        return 'guest_review_request';
    }

    public function category(): string
    {
        return 'trips';
    }

    protected function templateKey(): string
    {
        return 'review-request';
    }

    protected function url(): string
    {
        return route('guest.review', ['reservation' => $this->reservation->getKey()], false);
    }
}
