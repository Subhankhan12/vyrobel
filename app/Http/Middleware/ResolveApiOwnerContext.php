<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerContext;
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

        // Resolve the token user's owners + their org(s) cross-tenant (there is no request tenant yet).
        [$ownerIds, $role, $isPrimary, $orgIds] = $tenant->runAsSuperAdmin(function () use ($user): array {
            $primary = Owner::query()->where('user_id', $user->getKey())->get(['id', 'organization_id']);
            $subMemberships = OwnerUser::query()
                ->where('user_id', $user->getKey())->where('status', 'active')->get(['owner_id', 'role']);
            $subOwners = $subMemberships->isEmpty()
                ? collect()
                : Owner::query()->whereIn('id', $subMemberships->pluck('owner_id')->all())->get(['id', 'organization_id']);

            $ownerIds = $primary->pluck('id')->merge($subOwners->pluck('id'))
                ->map(fn ($id): string => (string) $id)->unique()->values()->all();

            $isPrimaryOwner = $primary->isNotEmpty();
            $role = $isPrimaryOwner
                ? OwnerTeamRole::CO_OWNER
                : $this->highestRole($subMemberships->pluck('role')->map(fn ($r): string => (string) $r)->all());

            $orgIds = $primary->pluck('organization_id')->merge($subOwners->pluck('organization_id'))
                ->map(fn ($id): string => (string) $id)->unique()->values()->all();

            return [$ownerIds, $role, $isPrimaryOwner, $orgIds];
        });

        // ⛔ Fail closed: a non-owner token, or one straddling organizations, reaches no owner data.
        if ($ownerIds === []) {
            abort(403, 'This token is not linked to an owner account.');
        }
        if (count($orgIds) !== 1) {
            abort(403, 'This token spans more than one organization.');
        }

        $organizationId = $orgIds[0];
        $tenant->set($organizationId);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
        app(OwnerContext::class)->activate($ownerIds, $role, $isPrimary);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
        app(TenantContext::class)->forget();
    }

    /**
     * The most-privileged role among a user's sub-user memberships (highest-first over the fixed order).
     *
     * @param  list<string>  $roles
     */
    private function highestRole(array $roles): ?string
    {
        foreach (OwnerTeamRole::ALL as $candidate) {
            if (in_array($candidate, $roles, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
