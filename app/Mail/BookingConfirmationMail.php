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
use Illuminate\Support\Carbon;

/**
 * Guest: booking confirmed (Phase 3 · Sub-gate 4) — sent on the REAL `reservation.confirmed` domain event to the
 * lead guest's own email. All figures are the reservation's real data; the total is `total_minor` via
 * {@see MoneyFormatter} (the same integer-minor figure the folio and PDFs use). No PII beyond the guest's own
 * name; goes only to the guest's own address.
 */
class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your booking is confirmed · '.((string) $this->reservation->reference));
    }

    public function content(): Content
    {
        $r = $this->reservation;
        $checkIn = Carbon::parse($r->check_in);
        $checkOut = Carbon::parse($r->check_out);

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'checkIn' => $checkIn->format('D, j M Y'),
                'checkOut' => $checkOut->format('D, j M Y'),
                'nights' => max(1, $checkIn->diffInDays($checkOut)),
                'total' => MoneyFormatter::display((int) $r->total_minor, (string) $r->currency),
            ],
        );
    }
}
