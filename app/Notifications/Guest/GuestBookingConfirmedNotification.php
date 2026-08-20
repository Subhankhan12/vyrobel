<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\BookingConfirmationMail;
use App\Models\Reservation;
use App\Notifications\FeedNotification;
use App\Notifications\Owner\OwnerNewBookingNotification;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Guest\GuestName;
use Illuminate\Support\Carbon;

/**
 * GUEST NOTIFICATIONS · G1 — the guest's own booking was confirmed.
 *
 * ⚠️ ADDITIVE TO THE EMAIL, NEVER A REPLACEMENT. `SendGuestTransactionalEmails` already sends
 * {@see BookingConfirmationMail} on this same `reservation.confirmed` event, and that listener is
 * untouched by this feature — it still fires exactly as before. This notification is the IN-APP surface of
 * the same message, dispatched from the notification dispatcher where the operator and owner fan-outs already
 * live. The `guest_booking_confirmed` catalog entry carries `email_default => false` so this can never become
 * a SECOND email (see the note there — it is load-bearing, not a preference).
 *
 * ⚠️ COPY IS SINGLE-SOURCED FROM THE EMAIL TEMPLATE, so the in-app row and the email cannot drift. The title
 * resolves through the SAME {@see EmailTemplateResolver} key (`booking-confirmation`) with the SAME merge
 * variables the Mailable uses, so an operator who customises the subject (#86) changes both surfaces at once.
 * ⚠️ Deliberately independent of {@see EmailTemplateResolver::enabled()}: turning the EMAIL off is a choice
 * about the email channel, not an instruction to withhold the in-app row — the guest still needs to know their
 * booking is confirmed. The in-app channel is governed by its own catalog entry.
 *
 * ⚠️ #28 — this is the guest's OWN booking, so their own name/reference is their own data, not a leak. The
 * payload still carries nothing about any OTHER person. Contrast the owner sibling
 * ({@see OwnerNewBookingNotification}), which withholds guest identity entirely
 * because the owner is a third party to the stay; here the notifiable IS the guest.
 *
 * ⚠️ The `url` points at the guest's own reservation — a route the authenticated guest can actually open
 * (`guest.reservation` resolves through the guest's own relations). A notification is a pointer; addressing
 * one at a page its recipient 403s is a dead affordance.
 */
class GuestBookingConfirmedNotification extends FeedNotification
{
    public function __construct(private Reservation $reservation) {}

    public function eventKey(): string
    {
        return 'guest_booking_confirmed';
    }

    public function category(): string
    {
        return 'trips';
    }

    public function data(object $notifiable): array
    {
        $r = $this->reservation;

        // ⚠️ PARSED, NOT METHOD-CALLED ON THE ATTRIBUTE — `check_in` is cast `date` but static analysis types
        // it as a string here, so calling ->format() directly is an L5 error. The same idiom the operator and
        // owner siblings use.
        $in = Carbon::parse((string) $r->check_in);
        $out = Carbon::parse((string) $r->check_out);
        $nights = (int) $in->diffInDays($out);

        $property = (string) ($r->property->name ?? 'your stay');
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        // THE ONE COPY SOURCE — the same resolver call the Mailable makes, with the same variables.
        $title = EmailTemplateResolver::for((string) $r->organization_id, 'booking-confirmation', [
            'guest_name' => GuestName::display($guest),
            'first_name' => $first !== '' ? $first : 'there',
            'unit' => $property,
            'nights' => (string) max(1, $nights),
            'reference' => (string) $r->reference,
        ])->subject();

        $context = $property.' · '.$in->format('j M Y').' – '.$out->format('j M Y');
        if ($nights > 0) {
            $context .= ' · '.$nights.' '.($nights === 1 ? 'night' : 'nights');
        }

        return [
            'title' => $title,
            'context' => $context,
            'url' => route('guest.reservation', ['reservation' => $r->getKey()], false),
            'category' => 'trips',
        ];
    }
}
