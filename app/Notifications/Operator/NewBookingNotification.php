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
        $checkIn = Carbon::parse($this->reservation->check_in)->format('j M');
        $checkOut = Carbon::parse($this->reservation->check_out)->format('j M');

        return [
            'title' => 'New booking · '.((string) $this->reservation->reference),
            'context' => ucfirst((string) $this->reservation->source).' · '.$checkIn.' – '.$checkOut,
            'url' => route('app.reservations', [], false),
            'category' => 'front_desk',
        ];
    }
}
