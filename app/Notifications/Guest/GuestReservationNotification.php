<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Models\Reservation;
use App\Notifications\FeedNotification;
use App\Services\NotificationPreferences;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Guest\GuestName;
use Illuminate\Support\Carbon;

/**
 * The shared shape for every RESERVATION-SCOPED guest notification (guest notifications · GATE 2).
 *
 * ⚠️ WHY A BASE AND NOT SIX COPIES. Gate 1 proved the pattern on one type with the copy-resolution inline.
 * Six more of the same would have been six independent copies of the rule "the row title is the email
 * template's subject" — and a second computation of one concept is precisely how surfaces drift (§N.8f).
 * The rule lives here once; a concrete type declares only what genuinely differs: its preference key, its
 * feed category, its email template key, and the guest-openable url it points at.
 *
 * ⚠️ COPY IS SINGLE-SOURCED FROM THE EMAIL. The title resolves through the SAME {@see EmailTemplateResolver}
 * call the matching Mailable makes, with the same merge variables, so an operator customising a subject (#86)
 * moves the in-app row and the email together. The variables are passed as a superset: every template's
 * declared vars are drawn from {guest_name, first_name, unit, nights, reference, balance}, and a placeholder
 * a given template does not use is simply never substituted.
 *
 * ⚠️ DELIBERATELY INDEPENDENT OF {@see EmailTemplateResolver::enabled()}. Turning an EMAIL off (#86) is a
 * choice about the email channel; it is not an instruction to withhold the in-app row. The in-app channel is
 * governed by the type's own catalog entry.
 *
 * ⚠️⚠️ EVERY CONCRETE TYPE'S CATALOG ENTRY MUST SET `email_default => false`. That is load-bearing, not a
 * preference: {@see NotificationPreferences::allows()} falls back to the catalog default for a
 * non-User notifiable, so a `true` would add the `mail` channel in {@see FeedNotification::via()} — and every
 * one of these events ALREADY sends the guest a Mailable, so the guest would be emailed TWICE.
 * `GuestNotificationsGate2Test` pins it per type.
 *
 * ⚠️ #28 — the notifiable IS the guest, so their own stay data is their own data. The payload still carries
 * nothing about any other person. (Contrast the OWNER siblings, which withhold guest identity entirely
 * because an owner is a third party to the stay.)
 */
abstract class GuestReservationNotification extends FeedNotification
{
    public function __construct(protected Reservation $reservation) {}

    /** The `EmailTemplateCatalog` key whose SUBJECT is this row's title — the one copy source. */
    abstract protected function templateKey(): string;

    /** The guest-openable route this row points at. A pointer at a page the guest cannot open is dead. */
    abstract protected function url(): string;

    /** Extra merge vars a specific template needs beyond the stay ones (e.g. `balance`). */
    protected function extraVars(): array
    {
        return [];
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'context' => $this->stayContext(),
            'url' => $this->url(),
            'category' => $this->category(),
        ];
    }

    /** THE ONE COPY SOURCE — the same resolver call the Mailable makes. */
    protected function title(): string
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, $this->templateKey(), array_merge([
            'guest_name' => GuestName::display($guest),
            'first_name' => $first !== '' ? $first : 'there',
            'unit' => (string) ($r->property->name ?? 'your stay'),
            'nights' => (string) max(1, $this->nights()),
            'reference' => (string) $r->reference,
        ], $this->extraVars()))->subject();
    }

    /** The one-line context both the feed page and the bell panel render: property · dates · nights. */
    protected function stayContext(): string
    {
        $r = $this->reservation;

        // ⚠️ PARSED, NOT METHOD-CALLED ON THE ATTRIBUTE — `check_in` is cast `date` but static analysis types
        // it as a string here, so calling ->format() directly is an L5 error. The idiom the operator and owner
        // siblings use.
        $in = Carbon::parse((string) $r->check_in);
        $out = Carbon::parse((string) $r->check_out);
        $nights = $this->nights();

        $context = (string) ($r->property->name ?? 'your stay')
            .' · '.$in->format('j M Y').' – '.$out->format('j M Y');

        if ($nights > 0) {
            $context .= ' · '.$nights.' '.($nights === 1 ? 'night' : 'nights');
        }

        return $context;
    }

    protected function nights(): int
    {
        return (int) Carbon::parse((string) $this->reservation->check_in)
            ->diffInDays(Carbon::parse((string) $this->reservation->check_out));
    }
}
