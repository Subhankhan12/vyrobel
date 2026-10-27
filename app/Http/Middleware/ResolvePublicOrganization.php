<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the public (unauthenticated) storefront-LANDING context from the `{organization}` route slug
 * and sets REAL tenant context — the org-level sibling of {@see ResolvePublicProperty}. The org is looked
 * up once under an explicit super-admin context (the only place the slug→org mapping is known), then
 * {@see TenantContext} + the spatie team id are set to that org for the rest of the request, so every
 * downstream query (properties, room types, reviews) runs scoped and can never read another tenant's data.
 *
 * No route-model binding (that would trip a tenant scope before context is set — the Phase 1 Step 9
 * gotcha); the slug is a plain string param the controller re-loads tenant-scoped.
 */
class ResolvePublicOrganization
{
    public const ATTRIBUTE = 'landing_organization_id';

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('organization');

        $tenant = app(TenantContext::class);
        // Live during trial AND while active; a suspended/cancelled org's public storefront is down.
        $organization = $tenant->runAsSuperAdmin(
            fn () => Organization::query()->where('slug', $slug)->whereIn('status', ['trialing', 'active'])->first(),
        );

        if ($organization === null) {
            abort(404, __('php/http/middleware/resolve_public_organization.unknown_organization'));
        }

        $tenant->set((string) $organization->getKey());
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->getKey());
        $request->attributes->set(self::ATTRIBUTE, $organization->getKey());

        return $next($request);
    }
}
