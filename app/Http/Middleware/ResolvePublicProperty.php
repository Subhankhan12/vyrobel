<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Property;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the public (unauthenticated) booking context from the `{property}` route slug and sets
 * REAL tenant context — it never bypasses the BelongsToOrganization guard. The property is looked
 * up once under an explicit super-admin context (the only place the slug→org mapping is known),
 * then {@see TenantContext} + the spatie team id are set to that org for the rest of the request.
 * The resolved property id is stashed for the controller, which re-loads it tenant-scoped — so
 * every downstream query runs scoped, and a request for property A can never read property B.
 *
 * No route-model binding (that would trip the tenant scope before context is set — the Phase 1
 * Step 9 gotcha); the slug is a plain string param.
 */
class ResolvePublicProperty
{
    public const ATTRIBUTE = 'booking_property_id';

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('property');

        $tenant = app(TenantContext::class);
        $property = $tenant->runAsSuperAdmin(
            fn () => Property::query()->where('slug', $slug)->where('status', 'active')->first(),
        );

        if ($property === null) {
            abort(404, 'Unknown property.');
        }

        $tenant->set((string) $property->organization_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($property->organization_id);
        $request->attributes->set(self::ATTRIBUTE, $property->getKey());

        return $next($request);
    }
}
