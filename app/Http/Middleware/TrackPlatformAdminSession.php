<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformAdmin;
use App\Support\Platform\PlatformAdminSessions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ADMIN 01 · PHASE 1 — enforces, on EVERY authenticated console request, the two things that must end a
 * platform-admin session without waiting for it to expire:
 *
 *   1. ⚠️⚠️ THE ADMIN IS NO LONGER ACTIVE. The login controller already refuses a disabled admin — but before
 *      this middleware, a session opened BEFORE the revocation carried on working until it timed out. A revoke
 *      that leaves the revoked person signed in is not a revoke.
 *   2. THE SESSION WAS FORCE-ENDED by an Owner (see {@see PlatformAdminSessions}), on any session driver.
 */
class TrackPlatformAdminSession
{
    public function __construct(private readonly PlatformAdminSessions $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof PlatformAdmin) {
            return $next($request);
        }

        if (! $admin->isActive()) {
            return $this->end($request, 'revoked', __('php/http/middleware/track_platform_admin_session.account_not_active'));
        }

        $verdict = $this->sessions->check($request, $admin);

        if ($verdict !== null) {
            return $this->end($request, null, __('php/http/middleware/track_platform_admin_session.session_ended'));
        }

        return $next($request);
    }

    private function end(Request $request, ?string $reason, string $message): Response
    {
        if ($reason !== null) {
            $this->sessions->endCurrent($request, $reason);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', $message);
    }
}
