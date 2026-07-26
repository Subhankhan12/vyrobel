<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SUPER ADMIN · GATE 0 — the gate for the admin 2FA LOGIN CHALLENGE (mirrors {@see RequireTwoFactorPending}
 * for the `admin` guard). The challenge page is reachable ONLY while a "2FA pending" state exists — the
 * `admin.2fa.pending_id` session key set after a correct password — AND no admin is authenticated yet.
 *
 * ⚠️ The pending marker is a plain session key, NOT the `admin` guard: a pending admin is not
 * `Auth::guard('admin')->check()`-true, so every authed /admin route (behind `auth:admin`) rejects them by
 * construction — the pending state grants NO access. This middleware just keeps the challenge PAGE off-limits
 * without a live pending state.
 */
class RequirePlatformAdminTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $request->session()->has('admin.2fa.pending_id')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
