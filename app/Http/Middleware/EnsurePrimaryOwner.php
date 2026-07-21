<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * THE OWNER-TEAM MANAGEMENT GATE (Phase 7c · SG3) — restricts the `/owner/team` routes to the PRIMARY OWNER
 * (the `owners.user_id` account holder). Team management (invite / change-role / remove) is held by NO
 * owner-team role — not even a co-owner — so it is gated on identity, not capability: this is the route
 * layer of {@see OwnerTeamRole::canAssign()}.
 *
 * Ordering: runs after {@see SetOwnerScopeForPortal} (the `/owner` group), which sets
 * {@see OwnerContext::isPrimaryOwner()}. FAIL-CLOSED: a sub-user (co-owner/accountant/view-only) or a
 * non-linked user is 403'd — so self-escalation is structurally impossible.
 */
class EnsurePrimaryOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(OwnerContext::class)->isPrimaryOwner()) {
            abort(403);
        }

        return $next($request);
    }
}
