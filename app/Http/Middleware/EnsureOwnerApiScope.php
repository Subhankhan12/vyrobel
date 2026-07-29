<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\OAuth\OAuthScopes;
use App\Support\Owner\OwnerContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The SCOPE + CAPABILITY gate for an owner API endpoint (Wave D · OAuth · Phase 3). Runs AFTER
 * {@see ResolveApiOwnerContext} has established the owner. TWO checks, both fail closed:
 *
 *   1. SCOPE — the token must carry the endpoint's required scope (`$user->tokenCan($scope)`), else 403.
 *      A `read:bookings` token hitting the statements endpoint (which requires `read:scope:statements`) is
 *      rejected here — a token can only reach the data its scope names.
 *   2. CAPABILITY — the scope must map to an owner-team capability the RESOLVED OWNER ROLE holds
 *      ({@see OwnerContext::can}), else 403. This is the "NO OVER-GRANT" guarantee: a token can never reach
 *      data the owner's own role can't (e.g. an accountant-role token carrying `read:bookings`, which needs
 *      `calendar.view` the accountant lacks, is refused).
 *
 * Combined with the OwnerScope + OrganizationScope (owner + tenant row isolation), this is the triple gate.
 */
class EnsureOwnerApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $user = $request->user();

        // 1) The token must carry this scope.
        if ($user === null || ! $user->tokenCan($scope)) {
            abort(403, 'The token does not carry the required scope: '.$scope.'.');
        }

        // 2) The scope must map to a capability the owner's role holds (no over-grant beyond the owner). P1-1:
        //    resolve this PER OWNER — the token may reach ONLY the owners whose role actually backs the scope's
        //    capability, never the union. We then NARROW the active owner set to exactly those owners, so the
        //    endpoint's reads (via the OwnerScope) can never return an owner the token's per-owner role can't.
        $capability = OAuthScopes::capabilityFor($scope);
        $context = app(OwnerContext::class);
        $capableOwnerIds = $capability !== null ? $context->ownerIdsWithCapability($capability) : [];
        if ($capableOwnerIds === []) {
            abort(403, 'This owner’s role does not permit the scope: '.$scope.'.');
        }
        $context->intersectOwners($capableOwnerIds);

        return $next($request);
    }
}
