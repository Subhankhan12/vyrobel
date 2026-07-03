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
        $ownerIds = [];

        if ($user instanceof User) {
            // Tenant-scoped: only owners in the active org that link to this user.
            $ownerIds = Owner::query()->where('user_id', $user->getKey())->pluck('id')->all();
        }

        // Always activate on the portal — an empty set fails closed (sees nothing), never all owners.
        $context->activate($ownerIds);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(OwnerContext::class)->deactivate();
    }
}
