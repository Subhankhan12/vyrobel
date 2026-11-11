<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerPortalAccess;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-establishes the TENANT + OWNER context for a Passport-authenticated API request (Wave D · OAuth · Phase 3).
 *
 * A token request is SESSION-LESS, so {@see SetTenantForWebRequest} / {@see SetOwnerScopeForPortal} never run.
 * This middleware does their job from the TOKEN's user: it resolves the owner(s) the user is linked to (primary
 * {@see Owner}s + active {@see OwnerUser} sub-memberships — the SAME resolution as the portal), sets
 * {@see TenantContext} to their organization + the spatie team, and activates {@see OwnerContext} for exactly
 * those owner ids with the user's real owner-team role.
 *
 * ⛔ FAIL CLOSED — the two isolation guards live here:
 *   • no linked owner  → 403 (a non-owner token can reach NO owner data; the OwnerScope would fail closed anyway).
 *   • owners in >1 org → 403 (a token is single-tenant BY DESIGN — it can never straddle two organizations).
 * The resolved {@see OwnerContext} then constrains every read to THIS owner's rows in THIS org (the OwnerScope +
 * OrganizationScope do the row-level work); the scope→capability gate ({@see EnsureOwnerApiScope}) runs after.
 * Contexts are torn down on {@see terminate()} so they never leak into a later request on a reused worker.
 */
class ResolveApiOwnerContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $tenant = app(TenantContext::class);

        // Resolve the token user's owners + their org(s) cross-tenant (there is no request tenant yet). The role
        // is recorded PER OWNER (P1-1) — a token inherits the user's real role on each owner, never a union role.
        // ⚠️ DEC-o — an owner whose portal an operator switched OFF is not resolved: their token (and their team's) reaches nothing
        //    until it is switched back on. The token itself is untouched, so it works again the moment the portal is.
        [$roles, $primaryOwnerIds, $orgIds, $switchedOff] = $tenant->runAsSuperAdmin(function () use ($user): array {
            $primary = Owner::query()->where('user_id', $user->getKey())->portalOn()->get(['id', 'organization_id']);
            $subMemberships = OwnerUser::query()
                ->where('user_id', $user->getKey())->where('status', 'active')
                ->whereNotIn('owner_id', OwnerPortalAccess::offOwnerIds())->get(['owner_id', 'role']);
            $subOwners = $subMemberships->isEmpty()
                ? collect()
                : Owner::query()->whereIn('id', $subMemberships->pluck('owner_id')->all())->get(['id', 'organization_id']);

            $roles = [];
            $primaryOwnerIds = [];
            foreach ($primary as $owner) {
                $roles[(string) $owner->id] = OwnerTeamRole::CO_OWNER;
                $primaryOwnerIds[] = (string) $owner->id;
            }
            foreach ($subMemberships as $membership) {
                $ownerId = (string) $membership->owner_id;
                if (! isset($roles[$ownerId])) {
                    $roles[$ownerId] = (string) $membership->role;
                }
            }

            $orgIds = $primary->pluck('organization_id')->merge($subOwners->pluck('organization_id'))
                ->map(fn ($id): string => (string) $id)->unique()->values()->all();

            // linked to an owner, but every owner they belong to is switched off — said plainly, not as "not an owner"
            return [$roles, $primaryOwnerIds, $orgIds, $roles === [] && OwnerPortalAccess::hasAnyLink($user)];
        });

        // ⛔ Fail closed: a non-owner token, or one straddling organizations, reaches no owner data.
        if ($switchedOff) {
            abort(403, __('php/http/middleware/resolve_api_owner_context.portal_off'));
        }
        if ($roles === []) {
            abort(403, 'This token is not linked to an owner account.');
        }
        if (count($orgIds) !== 1) {
            abort(403, 'This token spans more than one organization.');
        }

        $organizationId = $orgIds[0];
        $tenant->set($organizationId);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
        app(OwnerContext::class)->activatePerOwner($roles, $primaryOwnerIds);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
        app(TenantContext::class)->forget();
    }
}
