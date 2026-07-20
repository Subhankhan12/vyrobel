<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Guest: an outstanding balance before arrival (Phase 3 · Sub-gate 4). The `$balanceMinor` is the REAL folio
 * balance the caller computed (via the shared {@see FolioTotals}); the email never invents a
 * figure. Sent by the `emails:send-payment-reminders` command to guests arriving soon who still owe money — that
 * command is the real trigger; putting it on a daily schedule is the deferred piece.
 */
class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly int $balanceMinor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'A balance is due before your stay · '.((string) $this->reservation->reference));
    }

    public function content(): Content
    {
        $r = $this->reservation;

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'checkIn' => Carbon::parse($r->check_in)->format('D, j M Y'),
                'balance' => MoneyFormatter::display($this->balanceMinor, (string) $r->currency),
            ],
        );
    }
}
