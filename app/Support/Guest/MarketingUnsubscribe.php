<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Guest;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\URL;

/**
 * The ONE place a marketing unsubscribe link is minted (wireframe-parity sweep · HIGH-5).
 *
 * Marketing email could be sent with NO way to opt out: no unsubscribe route, no link in any template, no
 * `List-Unsubscribe` header. The only opt-out was a toggle inside a CLAIMED guest account, so a guest who never
 * claimed one had no route at all. {@see GuestMarketing::canReceive()} already gates the send on
 * `marketing_consent`; what was missing was any way for the guest to turn it off.
 *
 * The link is a native HMAC-`signed` URL (the same primitive the claim/verify and login-approval links use), so
 * it works WITHOUT logging in and cannot be forged or pointed at another guest. It deliberately does NOT expire:
 * an unsubscribe link must keep working in an old email, which is the whole point of one.
 *
 * ⚠️ MARKETING ONLY. Transactional mail — booking confirmations, refunds, payment reminders, pre-arrival — is a
 * service message the guest is owed regardless of consent, sits outside {@see GuestMarketing} by design, and must
 * NOT carry an unsubscribe link. Only the genuine marketing surfaces use this.
 */
final class MarketingUnsubscribe
{
    /** The signed page a guest lands on from an email (GET, no login). */
    public static function url(Guest $guest): string
    {
        return URL::signedRoute('marketing.unsubscribe', ['guest' => $guest->getKey()]);
    }

    /** The signed one-click endpoint an inbox provider POSTs to (RFC 8058). */
    public static function oneClickUrl(Guest $guest): string
    {
        return URL::signedRoute('marketing.unsubscribe.confirm', ['guest' => $guest->getKey()]);
    }

    /**
     * RFC 2369 / RFC 8058 headers for a MARKETING send, so an inbox client can offer its own unsubscribe button.
     *
     * Takes the already-minted one-click URL rather than the Guest, because a queued Mailable must not depend on
     * re-resolving a tenant-scoped model on a worker (the tenant-scoping trap that bit the booking confirmation).
     * A null URL yields EMPTY headers — a transactional mail must never advertise an unsubscribe it does not
     * honour. Returned as a {@see Headers} object because that is what `Mailable::headers()` must return; the
     * `Envelope` constructor carries no headers parameter.
     */
    public static function mailHeaders(?string $oneClickUrl): Headers
    {
        if ($oneClickUrl === null || $oneClickUrl === '') {
            return new Headers;
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<'.$oneClickUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
}
