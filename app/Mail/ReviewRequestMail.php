<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\Guest\GuestReviewController;
use App\Models\Reservation;
use App\Support\Guest\GuestName;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly ?string $reviewUrl = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'How was your stay? · '.((string) $this->reservation->reference));
    }

    public function content(): Content
    {
        $r = $this->reservation->loadMissing(['leadGuest', 'property', 'reservationUnits.unit']);

        $unit = $r->reservationUnits->first()?->unit;
        $unitName = $unit !== null ? trim((string) ($unit->name ?: $unit->code)) : '';

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

        return new Content(
            view: 'emails.review-request',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'unitName' => $unitName !== '' ? $unitName : (string) ($r->property->name ?? 'your unit'),
                'reference' => (string) $r->reference,
                'stay' => $checkIn->format('j').'–'.$checkOut->format('j M').' · '.$nights.' '.Str::plural('night', $nights),
                'reviewUrl' => $this->reviewUrl,
                'starUrls' => $starUrls,
            ],
        );
    }
}
