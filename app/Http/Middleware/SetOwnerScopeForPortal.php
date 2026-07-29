<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Activates the OWNER-PORTAL scope for owner-facing routes (Phase 3 · Module 1 Step 6). Runs AFTER
 * {@see SetTenantForWebRequest} (the tenant context must already be set) — it resolves the owner(s)
 * the authenticated user is linked to WITHIN the active tenant (`owners.user_id`) and activates
 * {@see OwnerContext} for them.
 *
 * SECURITY (fail-closed): the portal ALWAYS activates the scope. A user with no linked owner activates
 * it with an EMPTY set → the {@see OwnerScope} returns nothing (never all owners).
 * The scope is deactivated on {@see terminate()} so it can never leak into a later operator request.
 */
class SetOwnerScopeForPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(OwnerContext::class);
        $context->deactivate(); // start clean

        $user = $request->user();
        $roles = [];             // ownerId => the user's role ON THAT owner (P1-1: per-owner, never a union role)
        $primaryOwnerIds = [];

        if ($user instanceof User) {
            // ① Owners this user OWNS (the primary account holder) — the implicit Co-owner OF THAT owner.
            $primaryOwnerIds = Owner::query()->where('user_id', $user->getKey())->pluck('id')
                ->map(fn ($id): string => (string) $id)->all();
            foreach ($primaryOwnerIds as $ownerId) {
                $roles[$ownerId] = OwnerTeamRole::CO_OWNER;
            }

            // ② Owners this user is a SUB-USER of (Phase 7c owner team) — active memberships only. Both queries
            //    are tenant-scoped, so a sub-user only ever resolves owners in the active org (the org boundary
            //    holds); OwnerScope then restricts to those owner ids (the owner boundary holds). Nesting:
            //    sub-user ⊆ owner ⊆ org. The role is recorded PER OWNER — a co-owner of A + view-only of B is
            //    NEVER promoted to co-owner on B (the P1-1 over-grant). Primary ownership wins if both exist.
            $subMemberships = OwnerUser::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'active')
                ->get(['owner_id', 'role']);
            foreach ($subMemberships as $membership) {
                $ownerId = (string) $membership->owner_id;
                if (! isset($roles[$ownerId])) {
                    $roles[$ownerId] = (string) $membership->role;
                }
            }
        }

        // Always activate on the portal — an empty set fails closed (sees nothing), never all owners.
        $context->activatePerOwner($roles, $primaryOwnerIds);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
    }
}
