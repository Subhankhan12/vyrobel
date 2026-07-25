<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the signed-in user's saved language preference (`users.locale`) to the request.
 *
 * HONEST SCOPE: the preference is REAL — it persists and is applied here via App::setLocale. Vyrobel ships
 * only English translation strings today, so a non-'en' preference falls back to English at render time
 * (Laravel's default) — the choice is saved for when more languages land, and the Profile page says so
 * plainly. Wiring the switch now (rather than after translations) means no second change is needed then.
 *
 * Guests keep their own realm; this only reads the web-guard user, and never a request-supplied value —
 * so it can only ever apply the acting user's OWN preference.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (is_string($locale) && $locale !== '' && in_array($locale, config('app.available_locales', ['en']), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
