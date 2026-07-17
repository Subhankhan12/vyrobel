<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\LoginController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the 2FA login CHALLENGE. The challenge is reachable ONLY while a "2FA pending" state exists — the
 * `login.2fa.user_id` session key set by {@see LoginController} after a correct
 * password — AND the visitor is not yet authenticated.
 *
 * ⚠️ This is what makes the pending state SAFE. The pending marker is a plain session key, NOT the auth guard:
 * a pending user is not `Auth::check()`-true, so `/app` + `/owner` (behind `auth`) reject them by construction
 * — the pending state grants NO authenticated access anywhere. This middleware simply keeps the challenge
 * PAGE itself off-limits to anyone without a live pending state, and bounces an already-authenticated user
 * away (the challenge is moot for them).
 */
class RequireTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        // Already fully logged in → the challenge is moot; `/login` bounces an authenticated user home.
        if (Auth::check()) {
            return redirect()->route('login');
        }

        // No pending state → back to the start of login.
        if (! $request->session()->has('login.2fa.user_id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
