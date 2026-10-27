<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Reservation;
use App\Notifications\FeedNotification;
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
        $checkIn = Carbon::parse($this->reservation->check_in)->translatedFormat('j M');
        $checkOut = Carbon::parse($this->reservation->check_out)->translatedFormat('j M');

        return [
            'title' => $this->t('title', 'php/notifications/operator/new_booking_notification.title', ['reference' => (string) $this->reservation->reference]),
            'context' => $this->t('context', 'php/notifications/operator/new_booking_notification.context', ['source' => ucfirst((string) $this->reservation->source), 'check_in' => $checkIn, 'check_out' => $checkOut]),
            'url' => route('app.reservations', [], false),
            'category' => 'front_desk',
        ];
    }
}
