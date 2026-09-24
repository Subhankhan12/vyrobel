<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAdminRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ADMIN 01 · PHASE 1 — the ROLE gate on every platform-console route: `platform.can:<capability>`.
 *
 * Reads the ONE matrix ({@see PlatformAdminRole}) that the UI also reads, so a route and the button that leads
 * to it can never disagree.
 *
 * ⚠️ FAILS CLOSED three ways: no authenticated platform admin → login; an admin whose role is null/unknown or
 * whose status is not active → 403; a capability name that does not exist → a loud exception at request time
 * rather than a gate that silently admits (a typo in a route file must not become an open door).
 */
class EnsurePlatformCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if (! in_array($capability, PlatformAdminRole::allCapabilities(), true)) {
            throw new InvalidArgumentException("Unknown platform capability [{$capability}] on route [{$request->route()?->getName()}].");
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof PlatformAdmin) {
            return redirect()->route('admin.login');
        }

        if (! $admin->may($capability)) {
            abort(403, 'Your console role does not allow this.');
        }

        return $next($request);
    }
}
