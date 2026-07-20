<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: refund processed (Phase 3 · Sub-gate 4) — sent on the REAL `payment.refunded` domain event to the lead
 * guest's own email. The subject is the refund {@see Payment}; the amount is its real `amount_minor` via
 * {@see MoneyFormatter} (the same integer-minor figure the bounded-refund service wrote). The guest + reference
 * resolve through the refund's folio → reservation, so the email carries that booking's real details only.
 */
class RefundConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Payment $refund) {}

    public function envelope(): Envelope
    {
        $reference = (string) $this->reservation()->reference;

        return new Envelope(subject: 'Your refund has been processed'.($reference !== '' ? ' · '.$reference : ''));
    }

    public function content(): Content
    {
        $reservation = $this->reservation();

        return new Content(
            view: 'emails.refund-confirmation',
            with: [
                'guestName' => GuestName::display($reservation->leadGuest),
                'reference' => (string) $reservation->reference,
                'amount' => MoneyFormatter::display(abs((int) $this->refund->amount_minor), (string) $this->refund->currency),
            ],
        );
    }

    /** The booking this refund belongs to, via its folio. A refund always has a folio + reservation. */
    private function reservation(): Reservation
    {
        return $this->refund->folio->reservation;
    }
}
