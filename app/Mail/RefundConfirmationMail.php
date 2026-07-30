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
use Illuminate\Support\Carbon;

/**
 * Guest: refund processed (transactional-emails exact-match) — sent on the REAL `payment.refunded` domain event to
 * the lead guest's own email. The amount is the refund {@see Payment}'s real `amount_minor` (the same integer-minor
 * figure the bounded-refund service wrote); the guest + reference + stay resolve through the refund's folio →
 * reservation, so the email carries that booking's real details only.
 *
 * HONESTY: Payment stores NO card_last4 (token-only) → the destination is "your original payment method", never a
 * fabricated "···· 4242" (#28 — nothing to leak). The reason shows the reservation's real `cancellation_reason`
 * only when set, else omitted. The ETA is an honest range ("your bank may take longer"), never a guaranteed date.
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
        $reservation->loadMissing(['leadGuest', 'property']);

        $reason = is_string($reservation->cancellation_reason) && $reservation->cancellation_reason !== ''
            ? (string) $reservation->cancellation_reason
            : null;

        $processedAt = $this->refund->processed_at !== null ? Carbon::parse($this->refund->processed_at) : Carbon::now();

        $stay = null;
        if ($reservation->check_in !== null && $reservation->check_out !== null) {
            $stay = Carbon::parse($reservation->check_in)->format('j M').' – '.Carbon::parse($reservation->check_out)->format('j M Y');
        }

        return new Content(
            view: 'emails.refund-confirmation',
            with: [
                'guestName' => GuestName::display($reservation->leadGuest),
                'reference' => (string) $reservation->reference,
                'amount' => MoneyFormatter::display(abs((int) $this->refund->amount_minor), (string) $this->refund->currency),
                // HONEST: no card_last4 is stored — never a fabricated masked number.
                'method' => 'your original payment method',
                'reason' => $reason,
                'propertyName' => (string) ($reservation->property->name ?? 'your stay'),
                'stay' => $stay,
                'processedOn' => $processedAt->format('j M Y'),
                // HONEST ETA — a range, never a guaranteed arrival date.
                'etaLabel' => '5–10 business days',
                'reservationUrl' => route('guest.manage', $reservation->getKey()),
                'messagesUrl' => route('guest.messages'),
            ],
        );
    }

    /** The booking this refund belongs to, via its folio. A refund always has a folio + reservation. */
    private function reservation(): Reservation
    {
        return $this->refund->folio->reservation;
    }
}
