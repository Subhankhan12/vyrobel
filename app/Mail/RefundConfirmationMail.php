<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use App\Support\Money\RefundEta;
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
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Payment $refund) {}

    public function envelope(): Envelope
    {
        return $this->brandedEnvelope($this->template()->subject(), $this->brandFor($this->reservation()->property));
    }

    public function content(): Content
    {
        $reservation = $this->reservation();
        $reservation->loadMissing(['leadGuest', 'property']);

        $reason = filled($reservation->cancellation_reason) ? (string) $reservation->cancellation_reason : null;

        $processedAt = $this->refund->processed_at !== null ? Carbon::parse($this->refund->processed_at) : Carbon::now();

        // check_in / check_out are NOT NULL date columns on a reservation.
        $stay = Carbon::parse($reservation->check_in)->translatedFormat(LocalDate::pattern('j M')).' – '.Carbon::parse($reservation->check_out)->translatedFormat(LocalDate::pattern('j M Y'));

        $tpl = $this->template();

        return new Content(
            view: 'emails.refund-confirmation',
            with: [
                'guestName' => GuestName::display($reservation->leadGuest),
                'reference' => (string) $reservation->reference,
                'amount' => MoneyFormatter::display(abs((int) $this->refund->amount_minor), (string) $this->refund->currency),
                // HONEST: no card_last4 is stored — never a fabricated masked number.
                'method' => __('php/mail/refund_confirmation_mail.refund_method'),
                'reason' => $reason,
                'propertyName' => (string) ($reservation->property->name ?? __('php/mail/refund_confirmation_mail.your_stay')),
                'brand' => $this->brandFor($reservation->property),
                'stay' => $stay,
                'processedOn' => $processedAt->translatedFormat(LocalDate::pattern('j M Y')),
                // HONEST ETA — a range, never a guaranteed arrival date.
                'etaLabel' => RefundEta::window(),
                'reservationUrl' => route('guest.manage', $reservation->getKey()),
                'messagesUrl' => route('guest.messages'),
                // Operator-customizable template (#86)
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => ['status_timeline' => $tpl->block('status_timeline'), 'detail_rows' => $tpl->block('detail_rows')],
            ],
        );
    }

    /** The booking this refund belongs to, via its folio. A refund always has a folio + reservation. */
    private function reservation(): Reservation
    {
        return $this->refund->folio->reservation;
    }

    private function template(): ResolvedEmailTemplate
    {
        $r = $this->reservation();
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'refund-confirmation', [
            'first_name' => $first !== '' ? $first : __('php/mail/refund_confirmation_mail.there'),
            'reference' => (string) $r->reference,
        ]);
    }
}
