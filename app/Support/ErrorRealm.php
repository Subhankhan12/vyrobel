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
            'guest' => ['/guest/trips', 'Back to your trips'],
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
