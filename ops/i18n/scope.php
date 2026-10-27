<?php

declare(strict_types=1);

// ops/i18n/scope.php — I18N-01: which GET routes are in the translation's scope, and the realm each belongs to. ONE
// definition, read by render-all.php (and the repo's lang:todo grouping). Out of scope: the marketing pages and `/`
// (D7 — the founder's ruling), Horizon, the owner API, Livewire's own endpoints, webhooks, media, health checks, the
// Google/X redirects.

function vyI18nRealm(string $uri, ?string $name): ?string
{
    $u = '/'.ltrim($uri, '/');
    foreach (['/horizon', '/api', '/livewire', '/webhooks', '/stripe', '/media', '/storage', '/up', '/sitemap', '/oauth/token', '/oauth/clients',
        '/oauth/scopes', '/oauth/personal', '/auth/google', '/auth/twitter', '/_', '/channex', '/channels/webhook'] as $p) {
        if ($u === $p || str_starts_with($u, $p.'/') || str_starts_with($u, $p.'-')) {
            return null;
        }
    }
    if ($name !== null && str_starts_with($name, 'marketing.page')) {
        return null;
    }
    foreach ([['/app', 'Operator back office'], ['/owner', 'Owner portal'], ['/guest', 'Guest portal'], ['/admin', 'Platform admin console'],
        ['/book', 'Public booking engine'], ['/stay', 'Public booking engine'], ['/try-booking', 'Public booking engine'],
        ['/billing', 'Auth, signup and billing'], ['/signup', 'Auth, signup and billing'], ['/oauth', 'Auth, signup and billing']] as [$p, $realm]) {
        if ($u === $p || str_starts_with($u, $p.'/')) {
            return $realm;
        }
    }
    foreach (['/login', '/forgot-password', '/reset-password', '/two-factor-challenge', '/login-approval', '/email/verify', '/invite',
        '/team-invite', '/unsubscribe', '/status', '/security'] as $p) {
        if ($u === $p || str_starts_with($u, $p.'/')) {
            return 'Auth, signup and billing';
        }
    }

    return null;
}

/** @return list<array{realm: string, uri: string, name: ?string}> */
function vyI18nScopeRoutes(): array
{
    $out = [];
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }
        $realm = vyI18nRealm($route->uri(), $route->getName());
        if ($realm !== null) {
            $out[] = ['realm' => $realm, 'uri' => $route->uri(), 'name' => $route->getName()];
        }
    }
    usort($out, fn ($a, $b) => [$a['realm'], $a['uri']] <=> [$b['realm'], $b['uri']]);

    return $out;
}

/** Guest-realm pages a signed-out person sees (sign-in, claim, reset). @return list<string> */
function vyI18nGuestPublicNames(): array
{
    $names = [];
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        $n = (string) $route->getName();
        if (str_starts_with($n, 'guest.') && in_array('GET', $route->methods(), true)
            && ! in_array('Illuminate\Auth\Middleware\Authenticate:guest', app('router')->gatherRouteMiddleware($route), true)) {
            $names[] = $n;
        }
    }

    return $names;
}
