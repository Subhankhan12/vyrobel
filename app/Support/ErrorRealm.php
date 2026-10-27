<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\RoleAssignmentService;
use App\Support\Owner\OwnerContext;

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
     * ⚠️⚠️ F-50 — IS THE PERSON READING THIS ACTUALLY AN OWNER? The `/owner` 403 is reached by TWO different
     * people and the realm alone cannot tell them apart, because {@see self::current()} branches on the PATH.
     *
     * ⚠️ 🔬 BOTH AUDIENCES ARE REAL AND WERE MEASURED, not assumed:
     *   · **owner-linked** — a sub-user refused ONE capability ({@see EnsureOwnerCapability}), or a
     *     non-primary owner ({@see EnsurePrimaryOwner}), or a tampered id in an owner controller. For them
     *     "ask the primary owner to grant the capability" is exactly right.
     *   · **not owner-linked at all** — 🔬 reachable through the product's OWN path: revoke a staff user's
     *     last back-office role ({@see RoleAssignmentService::revoke()}, which guards the last
     *     ROLE-MANAGER but not the last role) and their membership stays active. They then meet
     *     {@see EnsureOwnerPortalAccess}'s 403 and are told to ask about a capability on an owner account
     *     they do not have.
     *
     * ⚠️ ONE DEFINITION (§N.8f): this asks `OwnerContext::actorIsOwnerLinked()` — the SAME call the gate
     * itself makes, already memoised for this request by the middleware that refused them. A second copy of
     * "is this an owner" on an error surface is exactly the drift this codebase keeps removing.
     *
     * ⚠️ FAIL-SAFE DIRECTION: if the question cannot be answered (no tenant context, CLI), it answers NO —
     * which routes the reader to `/`, a page that works for everybody. The opposite default would send them
     * back to the door that just refused them.
     */
    private static function ownerLinked(): bool
    {
        try {
            return app(OwnerContext::class)->actorIsOwnerLinked();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Where "back" goes for this realm, and what that control is called.
     *
     * @return array{0: string, 1: string}
     */
    public static function home(?string $realm = null): array
    {
        return match ($realm ?? self::current()) {
            'admin' => ['/admin', __('php/support/error_realm.back_to_console')],
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
                ? ['/guest/trips', __('php/support/error_realm.back_to_trips')]
                : ['/guest/claim', __('php/support/error_realm.access_bookings')],
            // ⚠️⚠️ F-50 — "Back to your portal" → `/owner` → REFUSED AGAIN. That loop is the finding, and it
            // is the THIRD time this shape has appeared here: F-27's paid-but-unclaimed window, F-47's claim
            // failure offering a door that needed the account being created, and now this. The class is
            // **a refusal that offers a way out which is not one.**
            //
            // ⚠️ 🔬 AND THE OBVIOUS SECOND CHOICE IS ALSO A TRAP: a reader who is not owner-linked was
            // MEASURED getting 403 on `/app/dashboard` too, and `/login` bounces them there. `/` is the only
            // destination that admitted them — established by following each one as that person, never by
            // picking the one that sounded right.
            'owner' => self::ownerLinked()
                ? ['/owner', __('php/support/error_realm.back_to_portal')]
                : ['/', __('php/support/error_realm.back_to_vyrobel')],
            'app' => ['/app/dashboard', __('php/support/error_realm.back_to_dashboard')],
            default => ['/', __('php/support/error_realm.back_to_safety')],
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
        return (string) match ($realm ?? self::current()) {
            'guest' => __('php/support/error_realm.forbidden_guest'),
            // ⚠️⚠️ F-50 — ONE MESSAGE WAS SERVING TWO READERS. The capability wording below is exactly right
            // for an owner sub-user and NONSENSE for somebody with no owner account, who was being told to
            // ask about a capability they could never be granted.
            'owner' => self::ownerLinked()
                ? __('php/support/error_realm.forbidden_owner')
                : __('php/support/error_realm.forbidden_not_owner'),
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
            'public' => __('php/support/error_realm.forbidden_public'),
            default => __('php/support/error_realm.forbidden_app'),
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
        return (string) match ($realm ?? self::current()) {
            'guest' => __('php/support/error_realm.support_guest'),
            'owner' => self::ownerLinked()
                ? __('php/support/error_realm.support_owner')
                : __('php/support/error_realm.support_not_owner'),
            default => __('php/support/error_realm.support_app'),
        };
    }
}
