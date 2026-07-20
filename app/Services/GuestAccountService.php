<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Guest\GuestClaimController;
use App\Mail\VerifyEmailMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Scopes\OrganizationScope;
use App\Support\Guest\GuestName;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Guest portal-account CLAIM (Phase 4a · Sub-gate 2). BOOKING-TIED: an account can be claimed ONLY for an email
 * that has a real booking; there is no free anonymous sign-up (a conscious divergence from the design's "Join for
 * member rates" framing — a guest with no booking cannot create an account).
 *
 * Everything is keyed on the BLIND INDEX ({@see Guest::emailHash()}), never the encrypted plaintext, and runs
 * WITHOUT the tenant scope (a claim is pre-tenant — the guest's org is derived from their row). The claim link is
 * a native, HMAC-signed, expiring {@see URL::temporarySignedRoute()} (the MustVerifyEmail primitive) — no token
 * stored, no PII in the URL (a guest ULID + a sha1 of the email, bound by the signature).
 *
 * NO ENUMERATION: the caller ({@see GuestClaimController}) ALWAYS returns the same
 * neutral response whether or not {@see claimableByEmail()} found anything — the link is only ever sent when it
 * did, but the response never reveals that.
 */
class GuestAccountService
{
    /**
     * The single guest row that can be claimed for this email: found by the blind index, still UNCLAIMED
     * (no password), and with at least one real booking. Null if the email is unknown, already claimed, or has
     * no booking — the booking-tied gate.
     */
    public function claimableByEmail(string $email): ?Guest
    {
        if (trim($email) === '') {
            return null;
        }

        return Guest::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('email_hash', Guest::emailHash($email))
            ->whereNull('password')   // no account yet
            ->get()
            ->first(fn (Guest $guest): bool => $this->hasBooking($guest));
    }

    /**
     * Resolve the guest a SIGNED claim link points at ({guest}/{hash}) — beyond the signature: the row must exist,
     * still be unclaimed, its email must still hash to the link's `hash` (so a link dies if the email changes),
     * and it must have a booking. Any failure → null (a generic "invalid link", no enumeration).
     */
    public function resolveClaimable(string $guestId, string $emailHash): ?Guest
    {
        $guest = Guest::query()->withoutGlobalScope(OrganizationScope::class)->whereKey($guestId)->first();
        if ($guest === null || $guest->password !== null) {
            return null;
        }

        $email = (string) $guest->email;
        if ($email === '' || ! hash_equals(sha1($email), $emailHash)) {
            return null;
        }

        return $this->hasBooking($guest) ? $guest : null;
    }

    /** Send the guest their signed, expiring claim link — wiring the Phase-3 Verify Email Mailable's trigger. */
    public function sendClaimLink(Guest $guest): void
    {
        $email = (string) $guest->email;
        if ($email === '') {
            return;
        }

        $url = URL::temporarySignedRoute('guest.claim.verify', now()->addHour(), [
            'guest' => $guest->getKey(),
            'hash' => sha1($email),
        ]);

        Mail::to($email)->send(new VerifyEmailMail(GuestName::display($guest), $url));
    }

    /** Complete the claim: set the (hashed) password + mark the email verified → the account is active. */
    public function claim(Guest $guest, string $password): Guest
    {
        $guest->password = $password;   // hashed by the model cast
        $guest->email_verified_at = now();
        $guest->save();                 // save() is scope-free → safe pre-tenant; the saving() hook keeps email_hash

        return $guest;
    }

    /** Does this guest row lead any reservation? (Unscoped — a lead_guest_id is inherently org-specific.) */
    private function hasBooking(Guest $guest): bool
    {
        return Reservation::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('lead_guest_id', $guest->getKey())
            ->exists();
    }
}
