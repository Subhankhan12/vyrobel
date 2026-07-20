<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Reservation;
use App\Support\Guest\GuestName;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: review request after a stay (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER is DEFERRED to Phase 5
 * (the Reviews module) — there is no `review.*` event and no review page to link to yet, so `$reviewUrl` is null
 * until Phase 5 wires it. Real stay context (guest name, property) from the reservation.
 */
class ReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly ?string $reviewUrl = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'How was your stay?');
    }

    public function content(): Content
    {
        $r = $this->reservation;

        return new Content(
            view: 'emails.review-request',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'reviewUrl' => $this->reviewUrl,
            ],
        );
    }
}
