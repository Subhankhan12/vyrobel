<?php

declare(strict_types=1);

namespace App\Mail;

use App\Console\Commands\SendPaymentReminders;
use App\Models\Folio;
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
 * Guest: an outstanding balance before arrival (transactional-emails exact-match). The `$balanceMinor` is the REAL
 * folio balance the caller ({@see SendPaymentReminders}) computed via the shared
 * {@see FolioTotals}; the email never invents a figure. Sent 3 days before arrival WHEN a balance is due (the
 * command skips a settled folio). Total / Paid / Balance all come from the same {@see FolioTotals} read
 * (Total − Paid = Balance, reconciling #17); the "Pay now" button points at the guest's real payments page.
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
        $r = $this->reservation->loadMissing(['leadGuest', 'property', 'reservationUnits.unit']);
        $currency = (string) $r->currency;

        // Total + Paid from the SAME FolioTotals read the balance came from — so Total − Paid == Balance to the
        // cent (#17). Fall back to the passed balance alone if the folio is absent.
        $folio = Folio::query()->where('reservation_id', $r->getKey())->first();
        $totals = $folio !== null ? FolioTotals::for($folio) : null;

        $unit = $r->reservationUnits->first()?->unit;
        $unitName = $unit !== null ? trim((string) ($unit->name ?: $unit->code)) : '';

        $balanceDisplay = MoneyFormatter::display($this->balanceMinor, $currency);

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'unitName' => $unitName !== '' ? $unitName : (string) ($r->property->name ?? 'your unit'),
                'arriving' => Carbon::parse($r->check_in)->format('D, j M Y'),
                'total' => $totals !== null ? MoneyFormatter::display($totals['charges_minor'], $currency) : null,
                'paid' => $totals !== null ? MoneyFormatter::display($totals['paid_minor'], $currency) : null,
                'balance' => $balanceDisplay,
                'payUrl' => route('guest.payments'),
                'messagesUrl' => route('guest.messages'),
            ],
        );
    }
}
