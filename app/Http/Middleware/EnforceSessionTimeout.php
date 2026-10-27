<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Platform\BreakGlass;
use App\Support\Security\SessionTimeout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SESSION TIMEOUT — signs a staff or owner account out after the inactivity THEY chose on their Security page
 * ({@see SessionTimeout}). Runs in the `web` group, after the session has started, and does nothing for anyone who chose
 * none (the default) — so the guest portal (its own guard) and every account without a choice are untouched.
 *
 * The clock is the time of the person's last OWN request, kept in their session; a page's background polling never moves it
 * ({@see SessionTimeout::isBackground()}). Past the limit: the sign-in ends here, the session is invalidated (the row is gone,
 * so it cannot be resumed), and the person lands on the sign-in page with the reason — a background request gets a 401.
 *
 * ⚠️ It stands aside for a Vyrobel support (break-glass) request, like EndSessionsOnPasswordChange: that request borrows the
 * person's view, writes nothing to their session, and ends by its own 30-minute rule.
 */
class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->has(BreakGlass::ATTRIBUTE)) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();
        $minutes = $user instanceof User ? SessionTimeout::minutesFor($user) : null;

        if ($minutes !== null && $request->hasSession()) {
            $last = $request->session()->get(SessionTimeout::KEY);

            if (is_int($last) && time() - $last > $minutes * 60) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = __('php/http/middleware/enforce_session_timeout.signed_out_idle', ['duration' => SessionTimeout::label($minutes)]);

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 401)
                    : redirect()->route('login')->with('status', $message);
            }

            if (! is_int($last) || ! SessionTimeout::isBackground($request)) {
                $request->session()->put(SessionTimeout::KEY, time());
            }
        }

        return $next($request);
    }
}
