<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\EnsureOwnerPortalAccess;
use App\Http\Middleware\ResolveApiOwnerContext;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ⚠️⚠️ DEC-o (2026-11-12) — "IS THIS OWNER'S PORTAL SWITCHED OFF?", answered in ONE place.
 *
 * An operator can switch an owner's portal access off and on from the owner's page. The state is `owners.portal_disabled_at` (null =
 * on) and it sits on the OWNER, not on a login — so:
 *   · the owner's team goes with the owner and comes back with them: their `owner_users` rows are never touched, they simply stop
 *     resolving to an owner whose portal is off ({@see OwnerContext::resolveForActor()}, {@see ResolveApiOwnerContext});
 *   · nobody's `users.status` or Membership changes, so a person who also works for the operator keeps their login and /app;
 *   · nothing is deleted — payouts, statements and the ledger do not read this at all.
 *
 * ⚠️ WHAT THIS DELIBERATELY IS NOT: {@see OwnerContext::actorIsOwnerLinked()} keeps answering "linked" for an owner whose portal is
 * off. The owner scope falls OPEN for an actor who is not owner-linked, so "off" must make the scope resolve to NOTHING (`1 = 0`),
 * never to everything — it does, because the resolvers drop the off owner and the active-but-empty scope fails closed.
 * The request gate ({@see EnsureOwnerPortalAccess}) is the first layer; the resolvers are the second (Livewire updates, the API).
 */
final class OwnerPortalAccess
{
    /**
     * The ids of the owners whose portal is off, as a subquery for `whereNotIn` — tenant-scoped like every Owner query (and
     * unscoped under the super-admin context the API resolver and sign-in routing run in).
     *
     * @return Builder<Owner>
     */
    public static function offOwnerIds(): Builder
    {
        return Owner::query()->portalOff()->select('id');
    }

    /** Is the user linked to ANY owner — as the primary owner or as an active team member — switched off or not? */
    public static function hasAnyLink(User $user): bool
    {
        return Owner::query()->where('user_id', $user->getKey())->exists()
            || OwnerUser::query()->where('user_id', $user->getKey())->where('status', 'active')->exists();
    }

    /** Is the user linked to at least one owner whose portal is ON (as the primary owner, or as an active team member)? */
    public static function hasOpenLink(User $user): bool
    {
        return Owner::query()->where('user_id', $user->getKey())->portalOn()->exists()
            || OwnerUser::query()->where('user_id', $user->getKey())->where('status', 'active')
                ->whereNotIn('owner_id', self::offOwnerIds())->exists();
    }
}
