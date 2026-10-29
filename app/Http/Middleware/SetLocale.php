<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\I18n\Locales;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The language of a SIGNED-IN person's request (I18N-01, 2026-10-27 — the founder's rulings on PLAN.md §5).
 *
 * Whose language wins (`App\Support\I18n\Locales::resolve` — only a FINISHED language is ever applied, else English):
 *  - the web guard (staff, owners): their own `users.locale` → their organisation's default → English;
 *  - the guest guard (its own column): `guests.language` → the guest's organisation's default → English;
 *  - on /admin, the platform admin's own `platform_admins.locale` → English (the console has no organisation default).
 * Signed out, this sets English; a visitor's own pick is SetVisitorLocale's job, which runs straight after and acts only
 * when nobody is signed in.
 *
 * Self-only: each realm's own authenticated subject is read here — never a request-supplied value — so this can only
 * ever apply the acting person's OWN preference (pinned by UnwiredControlsTest). Each guard is asked BY NAME: whichever
 * guard a route later makes the default, staff are the `web` guard and guests the `guest` guard, never each other. Runs
 * in the `web` group, so a Livewire update request speaks the same language as its page.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        $guestLanguage = $user === null ? $request->user('guest')?->language : null;
        $guest = $user === null ? $request->user('guest') : null;

        // the console speaks its admin's own language (platform_admins.locale) — on /admin only, so a staff session held in
        // the same browser keeps its own language everywhere else
        $admin = $request->is('admin', 'admin/*') ? $request->user('admin') : null;

        $locale = match (true) {
            $admin !== null => Locales::resolve($admin->locale ?? null),
            $user !== null => Locales::resolve($user->locale, Locales::organizationDefault(app(TenantContext::class)->id())),
            $guest !== null => Locales::resolve($guestLanguage, Locales::organizationDefault($guest->organization_id)),
            default => Locales::SOURCE,
        };

        App::setLocale($locale);

        return $next($request);
    }
}
