<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Property\PropertyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current PROPERTY working-context for a back-office (`/app`) request and activates the
 * {@see PropertyContext} (Phase 6a · multi-property).
 *
 * Runs on the `/app` group, AFTER {@see SetTenantForWebRequest} (global web) has set the tenant and
 * {@see EnsureBackOfficeAccess} has confirmed the user belongs in the back office — so the accessible
 * property set is already tenant-scoped and the actor is an operator.
 *
 * The selection lives in the session (`current_property_id`), scoped per user by the session itself.
 * It is VALIDATED against the user's accessible set inside {@see PropertyContext::activate()} — a
 * stale or foreign id (e.g. after switching org, or a hand-crafted value) never leaks: it falls back
 * to the user's first accessible property. Never throws, never 500s; an org with no properties yields
 * a null current property, which every property-scoped screen already tolerates.
 */
class SetCurrentPropertyForWebRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $accessibleIds = $user->accessiblePropertyIds();
            $selection = session('current_property_id');

            app(PropertyContext::class)->activate(
                $accessibleIds,
                is_string($selection) ? $selection : null,
            );
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(PropertyContext::class)->deactivate();
    }
}
