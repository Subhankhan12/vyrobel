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
        $ownerIds = [];
        $role = null;

        if ($user instanceof User) {
            // ① Owners this user OWNS (the primary account holder) — the implicit Co-owner.
            $primaryOwnerIds = Owner::query()->where('user_id', $user->getKey())->pluck('id')->all();

            // ② Owners this user is a SUB-USER of (Phase 7c owner team) — active memberships only. Both queries
            //    are tenant-scoped, so a sub-user only ever resolves owners in the active org (the org boundary
            //    holds); OwnerScope then restricts to those owner ids (the owner boundary holds). Nesting:
            //    sub-user ⊆ owner ⊆ org.
            $subMemberships = OwnerUser::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'active')
                ->get(['owner_id', 'role']);

            $ownerIds = array_values(array_unique(array_merge(
                $primaryOwnerIds,
                $subMemberships->pluck('owner_id')->map(fn ($id): string => (string) $id)->all(),
            )));

            // The acting role: a primary owner is Co-owner; otherwise the most-privileged sub-user role.
            if ($primaryOwnerIds !== []) {
                $role = OwnerTeamRole::CO_OWNER;
            } elseif ($subMemberships->isNotEmpty()) {
                $role = $this->highestRole($subMemberships->pluck('role')->map(fn ($r): string => (string) $r)->all());
            }
        }

        // Always activate on the portal — an empty set fails closed (sees nothing), never all owners.
        $context->activate($ownerIds, $role);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
    }

    /**
     * The most-privileged role among a user's sub-user memberships (a user could be a sub-user of several
     * owners at different roles; the acting role is the highest, in the fixed privilege order).
     *
     * @param  list<string>  $roles
     */
    private function highestRole(array $roles): ?string
    {
        foreach (OwnerTeamRole::ALL as $candidate) {   // highest-first
            if (in_array($candidate, $roles, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
