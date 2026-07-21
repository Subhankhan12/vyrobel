<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * THE OWNER-TEAM CAPABILITY GATE (Phase 7c · SG2) — refuses an owner-portal route to a sub-user whose
 * acting owner-team role ({@see OwnerTeamRole}) does not grant the required capability.
 *
 * This is authorization by ROUTE for a REQUEST fact about an ACTOR — the same discipline as invariant #30:
 * the acting role is a property of the request, not of the data row, so the gate lives at the route, not in
 * a service. It MUST run AFTER {@see SetOwnerScopeForPortal} (in the `/owner` group), which resolves the
 * acting role into {@see OwnerContext}; group middleware runs before route middleware, so ordering holds.
 *
 * SCOPE: the gate targets ACTUAL owner-team members — a user WITH owner access (the primary owner, or an
 * active sub-user; {@see OwnerContext::ownerIds()} non-empty) whose role lacks the capability is 403'd. A
 * user with NO owner access at all (non-linked member, disabled sub-user → empty owner scope) is left to the
 * existing owner-scope fail-closed (an empty read / a cross-owner 404), NOT short-circuited to 403 here — so
 * this gate composes with, rather than masks, the scope-level isolation the owner suite already proves.
 *
 * The primary owner acts as co_owner (full), so every existing owner flow is unaffected; only invited
 * sub-users are restricted to their role's capability grid.
 *
 * ⚠️ The sensitive PAYOUT account & settings ({@see OwnerTeamRole::CAP_PAYOUT}) is co-owner-ONLY through
 * this gate — an accountant or view-only sub-user reaching the encrypted-IBAN screen is a money-security
 * hole (invariant #28 on the IBAN). Applied on BOTH the read page and every write.
 */
class EnsureOwnerCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $context = app(OwnerContext::class);

        // Only a user who HAS owner access is capability-gated; a user with none falls through to the
        // owner-scope's own fail-closed (empty reads), preserving the scope-isolation proofs.
        if ($context->ownerIds() !== [] && ! $context->can($capability)) {
            abort(403);
        }

        return $next($request);
    }
}
