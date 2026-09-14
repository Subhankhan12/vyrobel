<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use App\Models\UserIdentity;
use App\Support\Authorization\RolePermissions;
use App\Support\Owner\OwnerLoginLifecycle;

/**
 * ⚠️⚠️ THE FLOOR: A USER MUST ALWAYS RETAIN AT LEAST ONE USABLE CREDENTIAL — a password, or a linked
 *    provider. Unlinking your only sign-in method is a PERMANENT LOCKOUT, and it is permanent in the way
 *    that matters: the person is still an active account, still holds their roles, and still cannot get in.
 *
 * ⚠️ THE CHECK LIVES HERE AND IS CALLED FROM THE UNLINK PATH — never from a view. A disabled button is a
 *    presentation detail that any direct POST goes straight around; the refusal has to be where the write is.
 *    The precedent is {@see OwnerLoginLifecycle}, which decides whether a login should
 *    still exist at the point the link is taken away rather than in whatever screen happened to ask.
 *
 * ── ⚠️⚠️ HOW THIS RELATES TO THE LAST-ADMIN FLOOR ({@see RolePermissions::roleManagersAfter}) ──
 * They are the same CLASS of guard — "do not leave someone, or some organisation, with no way in" — but they
 * do not interact, and that is a fact about the code rather than a hope:
 *
 *   • The last-admin floor counts role-managers whose `users.status` is {@see User::SIGN_IN_STATUS}.
 *   • Unlinking a provider changes `user_identities` ONLY. It never touches `users.status`, never touches
 *     roles, and never touches memberships.
 *
 * So an unlink cannot reduce an organisation's count of sign-in-able administrators, and the two floors
 * cannot combine into a state neither one saw. ⚠️ This is asserted by a test rather than left as reasoning,
 * because "these two guards don't interact" is exactly the kind of claim that quietly stops being true.
 */
final class SignInCredentials
{
    /**
     * ⚠️⚠️ WHAT "CAN THIS ACCOUNT SIGN IN" ACTUALLY MEANS — settled, and NOT what it looks like.
     *
     * The readers of that idea (the login query, the password-reset broker twice, and the last-admin floor)
     * all key on `users.status`, and NONE of them reasons from having a password. That is the correct
     * definition and it survives passwordless accounts, for a reason that is easy to miss:
     *
     *   ⚠️ THE PASSWORD-RESET BROKER'S LOOKUP IS SCOPED BY STATUS, NOT BY PASSWORD. An ACTIVE user with no
     *      password and no linked provider can still request a reset link and set one. They are recoverable,
     *      therefore counting them as able to sign in is right — not merely convenient.
     *
     * So: an active account can always get in, whatever credentials it holds today. This method exists to
     * say that once, next to the floor that depends on it, so that the next person to add a credential type
     * does not have to re-derive it.
     */
    public static function canSignIn(User $user): bool
    {
        return $user->status === User::SIGN_IN_STATUS;
    }

    /** How many usable credentials this account holds right now: a password counts as one, each link as one. */
    public static function count(User $user): int
    {
        return ($user->hasPassword() ? 1 : 0)
            + UserIdentity::query()->where('user_id', $user->getKey())->count();
    }

    /**
     * ⚠️ May this identity be removed? False when it is the last thing the account can sign in with.
     *
     * @param  string  $identityId  the `user_identities` row being removed
     */
    public static function mayUnlink(User $user, string $identityId): bool
    {
        if ($user->hasPassword()) {
            return true;   // the password survives the unlink
        }

        return UserIdentity::query()
            ->where('user_id', $user->getKey())
            ->whereKeyNot($identityId)
            ->exists();
    }

    /** ⚠️ The refusal says WHAT WILL HAPPEN — never a silently disabled control with no explanation. */
    public static function unlinkRefusalReason(): string
    {
        return 'This is the only way you can sign in. Set a password first, and then you can remove it.';
    }
}
