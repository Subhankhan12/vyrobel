<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
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
 * Guest: booking cancelled (transactional-emails exact-match #109) — sent on the REAL `reservation.cancelled`
 * domain event to the lead guest's own email. Matched to "Cancellation Email.html", email-safe. Branded
 * ({@see BrandsGuestMail}) + customizable per #86 (subject/heading/message/button + the refund-summary /
 * come-back blocks).
 *
 * HONESTY (#28/#39): TWO cases, never a fabricated refund. `$refundMinor` is null unless a genuine refund was
 * recorded — a plain cancel issues none (a real refund fires its OWN {@see RefundConfirmationMail}); the
 * itemized "deposit / non-refundable / waived" split + a masked card are NOT modelled and are refused (the
 * destination is "your original payment method", no `····`). The source is the booking's real category, never
 * a fabricated OTA brand (#85). No promo code is invented (there is no discount engine) — just a warm invite.
 */
class CancellationMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
        public readonly ?string $reason = null,
        public readonly ?int $refundMinor = null,
    ) {}

    public function envelope(): Envelope
    {
        return $this->brandedEnvelope($this->template()->subject(), $this->brandFor($this->reservation->property));
    }

    public function content(): Content
    {
        $r = $this->reservation;
        $r->loadMissing(['leadGuest', 'property']);
        $checkIn = Carbon::parse($r->check_in);
        $checkOut = Carbon::parse($r->check_out);
        $nights = max(1, (int) $checkIn->diffInDays($checkOut));

        // A real recorded refund (>0) → Case A; else the honest non-refundable / no-refund-due case.
        $refund = ($this->refundMinor !== null && $this->refundMinor > 0)
            ? MoneyFormatter::display($this->refundMinor, (string) $r->currency)
            : null;

        $tpl = $this->template();

        return new Content(
            view: 'emails.cancellation',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? __('php/mail/cancellation_mail.your_stay')),
                'stay' => $checkIn->translatedFormat(LocalDate::pattern('j M')).' – '.$checkOut->translatedFormat(LocalDate::pattern('j M Y')),
                'nights' => $nights,
                'guests' => $this->guestsLabel($r),
                'reason' => $this->reason !== null && $this->reason !== '' ? $this->reason : null,
                // Case A vs Case B — a real refund figure or an honest "no refund due".
                'refund' => $refund,
                // HONEST: no card_last4 stored (#28) — never a fabricated masked number.
                'refundMethod' => __('php/mail/cancellation_mail.refund_method'),
                'refundEta' => RefundEta::window(),
                'sourceLine' => $this->sourceLine($r),
                'browseUrl' => $this->browseUrl($r),
                'messagesUrl' => route('guest.messages'),
                'brand' => $this->brandFor($r->property),
                // #86 customizable template + toggleable blocks.
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => [
                    'refund_summary' => $tpl->block('refund_summary'),
                    'return_offer' => $tpl->block('return_offer'),
                ],
            ],
        );
    }

    /** "2 adults · 1 child" from the real counts. */
    private function guestsLabel(Reservation $r): string
    {
        $adults = max(1, (int) $r->adults);
        $children = (int) $r->children;
        $parts = [trans_choice('php/mail/cancellation_mail.adults', $adults, ['count' => $adults])];
        if ($children > 0) {
            $parts[] = trans_choice('php/mail/cancellation_mail.children', $children, ['count' => $children]);
        }

        return implode(' · ', $parts);
    }

    /** A guest-facing source line — the booking's real category, never a fabricated OTA brand (#85). */
    private function sourceLine(Reservation $r): string
    {
        $name = $r->property->name ?? null;

        return match ((string) $r->source) {
            'direct' => $name !== null ? __('php/mail/cancellation_mail.booked_direct_with', ['property' => (string) $name]) : __('php/mail/cancellation_mail.booked_direct_with_us'),
            'ota' => __('php/mail/cancellation_mail.booked_via_partner'),
            default => $name !== null ? __('php/mail/cancellation_mail.booked_with', ['property' => (string) $name]) : __('php/mail/cancellation_mail.booked_with_us'),
        };
    }

    /** The property's public storefront to browse again, or the guest inbox as a safe fallback. */
    private function browseUrl(Reservation $r): string
    {
        $slug = $r->property?->slug;

        return is_string($slug) && $slug !== '' ? route('booking.browse', $slug) : route('guest.messages');
    }

    private function template(): ResolvedEmailTemplate
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'cancellation', [
            'first_name' => $first !== '' ? $first : __('php/mail/cancellation_mail.there'),
            'unit' => (string) ($r->property->name ?? __('php/mail/cancellation_mail.your_stay')),
            'reference' => (string) $r->reference,
        ]);
    }
}
