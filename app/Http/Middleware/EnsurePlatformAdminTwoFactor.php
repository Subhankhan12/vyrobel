<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformAdmin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SUPER ADMIN · GATE 0 — MANDATORY 2FA for the platform-admin realm.
 *
 * 2FA is not optional here (unlike the tenant realms, where it is opt-in): the highest-privilege surface must
 * be behind a second factor. This gate sits on the authenticated /admin area (EXCEPT the logout + the 2FA
 * SETUP routes, which must stay reachable so an un-enrolled admin can complete enrollment). An authenticated
 * admin without ACTIVE 2FA ({@see PlatformAdmin::hasTwoFactorEnabled()}) is redirected to the setup screen and
 * cannot reach any real /admin page until they enrol.
 */
class EnsurePlatformAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        // Belt-and-braces — the group is already behind `auth:admin`; never assume it when failing closed.
        if (! $admin instanceof PlatformAdmin) {
            return redirect()->route('admin.login');
        }

        if (! $admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.2fa.setup');
        }

        return $next($request);
    }
}
