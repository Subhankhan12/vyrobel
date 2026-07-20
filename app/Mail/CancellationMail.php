<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Reservation;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: booking cancelled (Phase 3 · Sub-gate 4) — sent on the REAL `reservation.cancelled` domain event to the
 * lead guest's own email. Carries the real cancellation reason (from the event) and, when the caller has computed
 * one, the real refund amount via {@see MoneyFormatter}. Refund figures are never invented: `$refundMinor` is
 * null unless a genuine refund was recorded, and the email says so.
 */
class CancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
        public readonly ?string $reason = null,
        public readonly ?int $refundMinor = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your booking has been cancelled · '.((string) $this->reservation->reference));
    }

    public function content(): Content
    {
        $r = $this->reservation;
        $refund = ($this->refundMinor !== null && $this->refundMinor > 0)
            ? MoneyFormatter::display($this->refundMinor, (string) $r->currency)
            : null;

        return new Content(
            view: 'emails.cancellation',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'reason' => $this->reason !== null && $this->reason !== '' ? $this->reason : null,
                'refund' => $refund,
            ],
        );
    }
}
