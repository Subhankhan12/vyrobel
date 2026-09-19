<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The ONE derivation of "which realm is this error page being shown in" (guest fix pass, F5).
 *
 * ⚠️ WHY THIS EXISTS RATHER THAN A SECOND `match`. `errors/layout.blade.php` already branched the realm to pick
 * the back-link — which is why a guest correctly reads "Back to your trips". But `errors/403.blade.php`
 * hardcoded ONE operator-framed body ("your role… ask an administrator on your team") shown in EVERY realm,
 * including guest and owner, who have neither a role nor a team. The guest deep-parity audit swept all six
 * error views and found 403 is the only one that does this; the other five are written neutrally.
 *
 * ⚠️ The obvious fix — copy the layout's `match` into 403 — would create a SECOND definition of "which realm",
 * and two `match`es for one concept WILL drift (the anti-drift rule this codebase is built on). So the
 * derivation moves here and BOTH surfaces read it: the layout for the back-link, 403 for the body. Add a realm
 * once and both follow.
 *
 * The realms are exactly the four that already existed (admin / guest / owner / app) plus the public default —
 * no new realm is invented.
 */
final class ErrorRealm
{
    /** The realm key for the current request path: admin | guest | owner | app | public. */
    public static function current(): string
    {
        $path = request()->path();

        return match (true) {
            str_starts_with($path, 'admin') => 'admin',
            str_starts_with($path, 'guest') => 'guest',
            str_starts_with($path, 'owner') => 'owner',
            str_starts_with($path, 'app') => 'app',
            default => 'public',
        };
    }

    /**
     * Where "back" goes for this realm, and what that control is called.
     *
     * @return array{0: string, 1: string}
     */
    public static function home(?string $realm = null): array
    {
        return match ($realm ?? self::current()) {
            'admin' => ['/admin', 'Back to the console'],
            // ⚠️⚠️ A GUEST WHO CANNOT CLAIM HAS NO ACCOUNT, SO "your trips" IS A LOGIN WALL. The refusal copy
            // is already good — 403, no identity leak, the expiry named, a human route suggested — but its
            // only CLICKABLE route required the very account the person was trying to create. Someone whose
            // claim link expired simply left, and nothing in this product would ever report it.
            //
            // ⚠️ SO THE DESTINATION FOLLOWS THE SESSION, not the URL prefix. Signed in → their trips, which
            // was always right. NOT signed in → the claim door, which is `web`-only (no auth guard) and is
            // the one place a stranger can start over.
            //
            // ⚠️ ENUMERATION DISCIPLINE KEPT (#6's rule): this is the same public door anyone may open. It
            // reveals nothing about whether a booking exists — the wording does not change, only where the
            // link points, and it points somewhere that works.
            'guest' => auth('guest')->check()
                ? ['/guest/trips', 'Back to your trips']
                : ['/guest/claim', 'Access your bookings'],
            'owner' => ['/owner', 'Back to your portal'],
            'app' => ['/app/dashboard', 'Back to the dashboard'],
            default => ['/', 'Back to safety'],
        };
    }

    /**
     * The 403 body for this realm.
     *
     * ⚠️ ONLY THE OPERATOR REALMS TALK ABOUT ROLES AND TEAMS, because only they have them. A guest's access is
     * decided by which booking is theirs, not by a permission an administrator could grant — telling them to
     * ask their team administrator is advice they cannot act on. An owner sub-user's capabilities ARE granted,
     * but by their primary owner, never by an operator's "team".
     */
    public static function forbiddenMessage(?string $realm = null): string
    {
        return match ($realm ?? self::current()) {
            'guest' => 'This page belongs to a different booking, or it isn’t part of your account. If you reached it from a link we sent you, the link may have expired.',
            'owner' => 'Your owner account doesn’t include this area. If you need it, ask the primary owner on your account to grant the capability.',
            // ⚠️ NOT BRANCHED, DELIBERATELY — and this is a REPORTED observation, not an oversight.
            // The 'admin' realm falls through to the operator wording below, which tells a PLATFORM ADMIN to
            // "ask an administrator on your team" — they have neither. That reads like the same defect as
            // F-3, but F-3 is scoped to the two UNAUTHENTICATED doors (the 'public' realm above); a platform
            // admin is signed in, on their own guard. Branching it here also contradicts a certified
            // assertion (GuestPricingDisplayAnd403Test: admin === app), which records what the guest fix
            // pass did rather than a decision about admins. ⚠️ Changing it is a founder call, so it is
            // written down instead of taken.
            // ⚠️ F-3 — THE READER HERE IS NOT SIGNED IN. They have no role, no team and no administrator,
            // so "ask an administrator on your team to grant the permission" named three things that do
            // not exist for them. This says what is true and offers the one action that can help.
            // ⚠️ ENUMERATION DISCIPLINE KEPT (Fix 06): it does NOT say whether the page exists, whether an
            // account exists, or whether a link was merely expired versus never valid — distinguishing
            // those would be an oracle. One wording covers every reason a stranger lands here.
            'public' => 'This page isn’t available without signing in. If you followed a link we sent you, it may have expired or already been used — sign in and try again, or ask whoever sent it for a new one.',
            default => 'Your role doesn’t include this area. If you need it, ask an administrator on your team to grant the permission.',
        };
    }

    /**
     * The "still stuck?" line in the error-page footer.
     *
     * ⚠️ THIS FOOTER RENDERS ON ALL SIX ERROR VIEWS, so it was telling every reader in every realm to
     * "contact your Vyrobel administrator" (A1, found by RENDERING the realm-branched 403 — the defect sat
     * six lines below the one being fixed). A guest has no Vyrobel administrator and no way to act on that;
     * an owner's counterpart is their primary owner. Only the operator realms genuinely have one.
     *
     * ⚠️ IT READS THE SAME DERIVATION as the back-link and the 403 body — a third `match` on the path here
     * would be exactly the drift {@see current()} was extracted to prevent.
     */
    public static function supportLine(?string $realm = null): string
    {
        return match ($realm ?? self::current()) {
            'guest' => 'If this keeps happening, reply to your booking confirmation email and the property will help.',
            'owner' => 'If this keeps happening, contact the primary owner on your account, or your property manager.',
            default => 'If this keeps happening, contact your Vyrobel administrator.',
        };
    }
}
