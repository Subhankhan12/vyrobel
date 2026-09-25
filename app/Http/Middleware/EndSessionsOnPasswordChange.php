<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Platform\BreakGlass;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️⚠️ SESSIONS 01 (L16) — a session that notices its password changed underneath it, and ends.
 *
 * Laravel's {@see AuthenticateSession}: each session remembers the password hash it was signed in with; on every request
 * it compares that with the account's CURRENT hash and, on a mismatch, signs the session out. So a password change or
 * reset is fatal to every OTHER session of that account WHATEVER THE SESSION DRIVER — measured on production 2026-09-25,
 * the driver was `file` and nothing else could reach those sessions. The session that made the change stays signed
 * in: the middleware stores the new hash at the end of that same request.
 *
 * It is one layer of two (belt and braces, by the brief): the `database` driver makes sessions LISTABLE and
 * REVOCABLE (`ActiveSessions`); this makes a password change end them even if listing ever breaks. Per guard: it
 * checks whichever guard the route authenticated with (`auth:admin`, `auth:guest`, `auth`), under
 * `password_hash_<guard>`.
 *
 * ⚠️ ONE EXCEPTION — a break-glass request. There the viewed hotel user is BORROWED onto the `web` guard for one
 * request, and {@see EnterBreakGlassSession} promises nothing is written to the web session. The framework would store
 * that user's hash in the ADMIN's session — and a stale `password_hash_web` left there would sign out the next person
 * who logs in on that browser (the framework never clears the key at login). So it stands aside.
 */
class EndSessionsOnPasswordChange extends AuthenticateSession
{
    /** @param  Request  $request */
    public function handle($request, Closure $next): Response
    {
        if ($request->attributes->has(BreakGlass::ATTRIBUTE)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
