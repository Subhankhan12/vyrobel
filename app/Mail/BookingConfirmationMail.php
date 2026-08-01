<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Folio;
use App\Models\Reservation;
use App\Support\Email\CalendarLinks;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\CancellationPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Guest: booking confirmed (transactional-emails exact-match #109) — sent on the REAL `reservation.confirmed`
 * domain event to the lead guest's own email. Matched to "Booking Confirmation Email.html", email-safe (table +
 * inline CSS). Customizable per #86 (subject/heading/message/button + the price-breakdown / before-you-arrive /
 * calendar-links blocks via {@see EmailTemplateResolver}); an un-customized send is byte-faithful.
 *
 * RECONCILING (#17): the grand Total is `reservation->total_minor` (the same integer-minor the folio/PDFs use);
 * the price-breakdown lines are the REAL `folio_items` (Σ === charges === total). HONEST (#28/#39): NO masked
 * card is shown (Payment is token-only, SAQ A) — the paid-with row is "your original payment method" + a real
 * receipt link; the door code is a 48h POINTER only (#33 — never the code, which `PreArrivalMail` carries);
 * the calendar links are generated from real dates; the source is the booking's real category, never a brand.
 */
class BookingConfirmationMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

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
        $currency = (string) $r->currency;
        $tpl = $this->template();

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'reference' => (string) $r->reference,
                'propertyName' => (string) ($r->property->name ?? 'your stay'),
                'checkIn' => $checkIn->format('D, j M Y'),
                'checkOut' => $checkOut->format('D, j M Y'),
                'checkInTime' => $this->timeLabel($r->property?->check_in_time, 'from '),
                'checkOutTime' => $this->timeLabel($r->property?->check_out_time, 'by '),
                'nights' => $nights,
                'guests' => $this->guestsLabel($r),
                // #17 — the reconciling grand total (same figure the folio/PDFs use).
                'total' => MoneyFormatter::display((int) $r->total_minor, $currency),
                // Real folio line items (Σ === total). Empty ⇒ the breakdown degrades to the Total row only.
                'lines' => $this->breakdown($r, $nights, $currency),
                'receiptUrl' => route('guest.receipt', $r->getKey()),
                'reservationUrl' => route('guest.manage', $r->getKey()),
                'arrivalUrl' => route('guest.arrival', $r->getKey()),
                'checkinUrl' => route('guest.checkin', $r->getKey()),
                'messagesUrl' => route('guest.messages'),
                'sourceLine' => $this->sourceLine($r),
                'freeCancelUntil' => CancellationPolicy::freeUntilLabel($r),
                'calendar' => CalendarLinks::for($r),
                'brand' => $this->brandFor($r->property),
                // #86 customizable template + toggleable blocks.
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => [
                    'price_breakdown' => $tpl->block('price_breakdown'),
                    'before_you_arrive' => $tpl->block('before_you_arrive'),
                    'calendar_links' => $tpl->block('calendar_links'),
                ],
            ],
        );
    }

    /**
     * The REAL price-breakdown lines from the folio's items (never a fabricated nightly split — the room is
     * stored as one line). Returns [] when no folio exists yet, so the blade shows only the reconciling Total.
     *
     * @return list<array{label: string, amount: string, muted: bool}>
     */
    private function breakdown(Reservation $r, int $nights, string $currency): array
    {
        $folio = Folio::query()->where('reservation_id', $r->getKey())->with('items')->first();
        if ($folio === null) {
            return [];
        }

        return $folio->items->map(function ($item) use ($nights, $currency): array {
            $type = (string) $item->type;
            $label = $type === 'room'
                ? 'Accommodation · '.$nights.' '.Str::plural('night', $nights)
                : (string) $item->description;

            return [
                'label' => $label,
                'amount' => MoneyFormatter::display((int) $item->amount_minor, $currency),
                'muted' => in_array($type, ['tax', 'fee'], true),
            ];
        })->all();
    }

    /** "2 adults · 1 child" from the real counts. */
    private function guestsLabel(Reservation $r): string
    {
        $adults = max(1, (int) $r->adults);
        $children = (int) $r->children;
        $parts = [$adults.' '.Str::plural('adult', $adults)];
        if ($children > 0) {
            $parts[] = $children.' '.Str::plural('child', $children);
        }

        return implode(' · ', $parts);
    }

    /** The property's real check-in/out time, prefixed (e.g. "from 15:00"), or '' when unset. */
    private function timeLabel(mixed $time, string $prefix): string
    {
        if ($time === null || (string) $time === '') {
            return '';
        }

        return $prefix.Carbon::parse((string) $time)->format('H:i');
    }

    /** A guest-facing source line — the booking's real category, never a fabricated OTA brand (#85). */
    private function sourceLine(Reservation $r): string
    {
        $name = (string) ($r->property->name ?? 'us');

        return match ((string) $r->source) {
            'direct' => 'Booked direct with '.$name,
            'ota' => 'Booked via a travel partner',
            default => 'Booked with '.$name,
        };
    }

    private function template(): ResolvedEmailTemplate
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'booking-confirmation', [
            'guest_name' => GuestName::display($guest),
            'first_name' => $first !== '' ? $first : 'there',
            'unit' => (string) ($r->property->name ?? 'your stay'),
            'nights' => (string) max(1, (int) Carbon::parse($r->check_in)->diffInDays(Carbon::parse($r->check_out))),
            'reference' => (string) $r->reference,
        ]);
    }
}
