<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Support\Owner\OwnerContext;
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

        // ⚠️ ONE DEFINITION (audit E-1). The owner-link resolution lives in OwnerContext::resolveForActor()
        // because OwnerScope must be able to run the SAME resolution lazily for a request that never reached
        // this middleware (`/livewire/update`). Two copies would drift on a security boundary (§N.8f).
        if ($user instanceof User) {
            $context->resolveForActor($user);
        } else {
            // Not a User (guest realm / unauthenticated): activate EMPTY → fail closed, never all owners.
            $context->activatePerOwner([], []);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
    }
}
