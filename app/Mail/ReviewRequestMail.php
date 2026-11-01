<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\Guest\GuestReviewController;
use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Guest: review request after a stay (transactional-emails exact-match). Sent on the REAL
 * `reservation.checked_out` event (Phase 5 wired the trigger) to the lead guest's own email — ONCE per stay
 * (one review per reservation is a DB unique). `$reviewUrl` is the real `guest.review` page for THIS stay.
 *
 * The star-tap deep-links carry `?rating=N` so tapping a star opens the review with that rating pre-selected
 * ("already halfway done") — read by {@see GuestReviewController::show()}. The host
 * byline is the honest property team, never a fabricated named person (#39).
 */
class ReviewRequestMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly ?string $reviewUrl = null) {}

    public function envelope(): Envelope
    {
        return $this->brandedEnvelope($this->template()->subject(), $this->brandFor($this->reservation->property));
    }

    public function content(): Content
    {
        $r = $this->reservation->loadMissing(['leadGuest', 'property', 'reservationUnits.unit']);

        $unit = $r->reservationUnits->first()?->unit;
        $unitName = $unit !== null ? trim((string) ($unit->name ?: $unit->code)) : '';
        $unitName = $unitName !== '' ? $unitName : (string) ($r->property->name ?? __('php/mail/review_request_mail.your_unit'));

        $checkIn = Carbon::parse($r->check_in);
        $checkOut = Carbon::parse($r->check_out);
        $nights = max(1, (int) $checkIn->diffInDays($checkOut));

        // Star deep-links — each opens the real review page with that rating pre-selected. Derived from the
        // real reservation route (only when reviews are live, i.e. a $reviewUrl was supplied by the trigger).
        $starUrls = [];
        if ($this->reviewUrl !== null) {
            foreach (range(1, 5) as $n) {
                $starUrls[$n] = route('guest.review', ['reservation' => $r->getKey(), 'rating' => $n]);
            }
        }

        $tpl = $this->template($unitName, $nights);

        return new Content(
            view: 'emails.review-request',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'propertyName' => (string) ($r->property->name ?? __('php/mail/review_request_mail.your_stay')),
                'brand' => $this->brandFor($r->property),
                'unitName' => $unitName,
                'reference' => (string) $r->reference,
                'stay' => trans_choice('php/mail/review_request_mail.stay_line', $nights, ['count' => $nights, 'dates' => $checkIn->format('j').'–'.$checkOut->translatedFormat(LocalDate::pattern('j M'))]),
                'reviewUrl' => $this->reviewUrl,
                'starUrls' => $starUrls,
                // ── Operator-customizable template (#86) — the store drives these; default == the strings above ──
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => [
                    'tap_a_star' => $tpl->block('tap_a_star'),
                    'stay_recap' => $tpl->block('stay_recap'),
                    'signature' => $tpl->block('signature'),
                ],
            ],
        );
    }

    /** The resolved (store ?? default) customization for this email, with the real merge variables. */
    private function template(?string $unitName = null, ?int $nights = null): ResolvedEmailTemplate
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'review-request', [
            'first_name' => $first !== '' ? $first : __('php/mail/review_request_mail.there'),
            'unit' => $unitName ?? (string) ($r->property->name ?? __('php/mail/review_request_mail.your_unit')),
            'nights' => $nights ?? '',
            'reference' => (string) $r->reference,
        ]);
    }
}
