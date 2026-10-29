<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Reservation;
use App\Notifications\FeedNotification;
use App\Support\Notifications\FeedParams;
use Illuminate\Support\Carbon;

/**
 * Operator: a new booking was confirmed (Phase 3) — dispatched on the REAL `reservation.confirmed` domain event
 * to the org's operator staff. Front-desk category; links to the reservations list.
 */
class NewBookingNotification extends FeedNotification
{
    public function __construct(private Reservation $reservation) {}

    public function eventKey(): string
    {
        return 'new_booking';
    }

    public function category(): string
    {
        return 'front_desk';
    }

    public function data(object $notifiable): array
    {
        // dates and the source are said when the row is READ (FeedParams), so they follow the reader's language
        $checkIn = FeedParams::date(Carbon::parse($this->reservation->check_in), 'j M');
        $checkOut = FeedParams::date(Carbon::parse($this->reservation->check_out), 'j M');

        return [
            'title' => $this->t('title', 'php/notifications/operator/new_booking_notification.title', ['reference' => (string) $this->reservation->reference]),
            'context' => $this->t('context', 'php/notifications/operator/new_booking_notification.context', ['source' => FeedParams::source((string) $this->reservation->source), 'check_in' => $checkIn, 'check_out' => $checkOut]),
            'url' => route('app.reservations', [], false),
            'category' => 'front_desk',
        ];
    }
}
