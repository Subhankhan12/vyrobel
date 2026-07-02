<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the Step-1 deferred item: for authenticated WEB requests, resolve the user's active
 * organization and set BOTH the {@see TenantContext} (for the global scope / auto-fill / guard)
 * AND spatie's team id (for role/permission checks). Keeps CLI/queue/tests on programmatic
 * context — this only runs in the web middleware group.
 *
 * "Active organization" = a `active_organization_id` session value (if the user is a member),
 * otherwise the user's first active membership.
 */
class SetTenantForWebRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $organizationId = $this->resolveActiveOrganizationId($user);

            if ($organizationId !== null) {
                app(TenantContext::class)->set($organizationId);
                app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
            }
        }

        return $next($request);
    }

    private function resolveActiveOrganizationId(User $user): ?string
    {
        $memberOrgIds = $user->memberships()
            ->where('status', 'active')
            ->pluck('organization_id');

        $sessionOrgId = session('active_organization_id');
        if (is_string($sessionOrgId) && $memberOrgIds->contains($sessionOrgId)) {
            return $sessionOrgId;
        }

        $first = $memberOrgIds->first();

        return is_string($first) ? $first : null;
    }
}
