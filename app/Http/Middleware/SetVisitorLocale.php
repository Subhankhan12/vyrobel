<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\I18n\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The language of a SIGNED-OUT visitor (I18N-01 — the public booking engine and the sign-in pages). The founder's ruling:
 * **English is the default**, never the browser's language; the visitor picks another from the language menu, which links
 * to the SAME page with `?lang=<code>` (no `/{locale}/` route — risk E1 cannot fire), and the pick is remembered in the
 * `vy_lang` cookie. Only a FINISHED language is ever applied. Does nothing for anyone signed in (SetLocale has applied their
 * own — a platform admin's on /admin too), so a signed-in person can never be switched by a link.
 *
 * The booking engine copies the language in use onto the guest when they book (BookingEngineService), so their
 * confirmation and pre-arrival emails arrive in it.
 */
class SetVisitorLocale
{
    public const COOKIE = 'vy_lang';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('web') !== null || $request->user('guest') !== null
            || ($request->is('admin', 'admin/*') && $request->user('admin') !== null)) {
            return $next($request);
        }

        $picked = $request->isMethod('GET') ? $request->query('lang') : null;
        $remember = is_string($picked) && Locales::isFinished($picked) ? $picked : null;
        $cookie = $request->cookie(self::COOKIE);

        App::setLocale(Locales::resolve($remember, is_string($cookie) ? $cookie : null));

        $response = $next($request);

        if ($remember !== null && $cookie !== $remember) {
            $response->headers->setCookie(Cookie::make(self::COOKIE, $remember, 60 * 24 * 365, secure: $request->isSecure(), httpOnly: true, sameSite: 'lax'));
        }
        // a signed-out page's language can depend on the cookie — no shared cache may hand one visitor's page to another
        $response->setVary('Cookie', false);

        return $response;
    }
}
