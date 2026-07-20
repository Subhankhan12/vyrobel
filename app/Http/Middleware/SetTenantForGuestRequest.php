<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Guest;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 4b — for an authenticated GUEST request, set the {@see TenantContext} to the guest's OWN organization (a
 * guest belongs to exactly one org, stamped on their row), so the portal's tenant-scoped reads (reservations,
 * folios, units) resolve instead of throwing the no-context guard.
 *
 * ⚠️ This sets the ORG context only — it is NOT the isolation. A guest shares its org with other guests, so the
 * org context alone would expose the whole org. The isolation is that every controller resolves a booking THROUGH
 * the authed guest's own relations ({@see Guest::reservations()} — filtered by `lead_guest_id`), so a
 * foreign booking never resolves → 404. No spatie team is set (a guest holds no roles).
 */
class SetTenantForGuestRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $guest = Auth::guard('guest')->user();

        if ($guest instanceof Guest && ($organizationId = (string) $guest->organization_id) !== '') {
            app(TenantContext::class)->set($organizationId);
        }

        return $next($request);
    }
}
