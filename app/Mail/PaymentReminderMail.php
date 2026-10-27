<?php

declare(strict_types=1);

namespace App\Mail;

use App\Console\Commands\SendPaymentReminders;
use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Folio;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
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
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly int $balanceMinor) {}

    public function envelope(): Envelope
    {
        // ⚠️ BRAND REGRESSION (audit 2026-08-07 · §5 parity). This is a guest MONEY email — it asks the
        // guest to pay — and it was the only one of its siblings sending as the generic Vyrobel brand.
        // BookingConfirmation, Cancellation, RefundConfirmation, PreArrival and ReviewRequest all resolve
        // the property brand through the shared trait; a payment request arriving from a different sender
        // than the booking confirmation is exactly what a guest is taught to treat as a phishing attempt.
        return $this->brandedEnvelope(
            $this->template()->subject(),
            $this->brandFor($this->reservation->property),
        );
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
        $unitName = $unitName !== '' ? $unitName : (string) ($r->property->name ?? __('php/mail/payment_reminder_mail.your_unit'));

        $balanceDisplay = MoneyFormatter::display($this->balanceMinor, $currency);
        $tpl = $this->template($unitName, $balanceDisplay);

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? __('php/mail/payment_reminder_mail.your_stay')),
                'unitName' => $unitName,
                'arriving' => Carbon::parse($r->check_in)->translatedFormat('D, j M Y'),
                'total' => $totals !== null ? MoneyFormatter::display($totals['charges_minor'], $currency) : null,
                'paid' => $totals !== null ? MoneyFormatter::display($totals['paid_minor'], $currency) : null,
                'balance' => $balanceDisplay,
                'payUrl' => route('guest.payments'),
                'messagesUrl' => route('guest.messages'),
                // Operator-customizable template (#86)
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => ['totals' => $tpl->block('totals'), 'booking_detail' => $tpl->block('booking_detail')],
                // The shared emails.layout reads this to render the property's mark/colour (task #98).
                'brand' => $this->brandFor($r->property),
            ],
        );
    }

    private function template(?string $unitName = null, ?string $balance = null): ResolvedEmailTemplate
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'payment-reminder', [
            'first_name' => $first !== '' ? $first : __('php/mail/payment_reminder_mail.there'),
            'unit' => $unitName ?? (string) ($r->property->name ?? __('php/mail/payment_reminder_mail.your_unit')),
            'reference' => (string) $r->reference,
            'balance' => $balance ?? MoneyFormatter::display($this->balanceMinor, (string) $r->currency),
        ]);
    }
}
