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
use Illuminate\Support\Carbon;

/**
 * Guest: pre-arrival info — directions & access (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER (send N days
 * before check-in) is a scheduled command, deferred honestly (same shape as the payment-reminder command). The
 * directions are the property's REAL address. There is no unit access-code column yet, so `$accessCode` is null
 * unless a real one is passed — the email then says the code will follow, rather than fabricating one.
 */
class PreArrivalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly ?string $accessCode = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Getting ready for your stay · '.((string) $this->reservation->reference));
    }

    public function content(): Content
    {
        $r = $this->reservation;
        $address = $r->property->address ?? [];
        $directions = trim(implode(', ', array_filter(array_map('strval', $address))));

        return new Content(
            view: 'emails.pre-arrival',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'checkIn' => Carbon::parse($r->check_in)->format('D, j M Y'),
                'directions' => $directions !== '' ? $directions : null,
                'accessCode' => $this->accessCode,
            ],
        );
    }
}
