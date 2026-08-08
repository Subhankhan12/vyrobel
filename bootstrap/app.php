<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantForWebRequest;
use App\Support\Auth\TrustedDeviceService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Wave D · OAuth — the scoped, token-authenticated owner API (prefix /api). Session realms untouched:
        // these routes use the `api` (passport) guard only. JSON errors are already primed (shouldRenderJsonWhen).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Resolve the active organization from the authenticated user on every web request,
        // syncing TenantContext + spatie team id. CLI/queue/tests use programmatic context.
        $middleware->web(append: [
            SetTenantForWebRequest::class,
            // Apply the signed-in user's saved language preference (English-only strings today; the
            // preference is real and persists — see App\Http\Middleware\SetLocale).
            SetLocale::class,
        ]);

        // Security response headers on EVERY web response, all five realms (audit 2026-08-07 — the app sent
        // none at all, so every money surface was framable). Prepended so the headers are set even when a
        // later middleware short-circuits the request (a 403 from a permission gate, a redirect from an auth
        // guard, a 429 from the login throttle) — those responses need the headers just as much as a 200.
        // The policy is built from an inventory of what the app really loads; see the class docblock.
        $middleware->web(prepend: [
            SecurityHeaders::class,
        ]);

        // TRUSTED PROXIES (audit 2026-08-07 · F6-10). There was no configuration at all, so Laravel trusted
        // NO proxy — a safe default (a spoofed X-Forwarded-For is ignored, so no bypass), but behind a real
        // load balancer it means every request appears to come from the LB. Three things then break QUIETLY:
        //   • EnforceIpAllowlist compares the PROXY's address, so the operator IP allowlist blocks everyone
        //     (that middleware's own docblock names this as its deployment prerequisite);
        //   • per-IP rate limiting collapses into a single bucket for the whole internet;
        //   • $request->isSecure() reads false behind a TLS-terminating LB, which would suppress HSTS and
        //     defeat the secure-cookie default above.
        //
        // Still opt-in and still safe by default: with TRUSTED_PROXIES unset the behaviour is exactly as
        // before (trust nothing). Set it to '*' when the app is only reachable through the LB, or to a
        // comma-separated list of proxy addresses.
        $proxies = trim((string) env('TRUSTED_PROXIES', ''));

        if ($proxies !== '') {
            $middleware->trustProxies(
                at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }

        // Unauthenticated web requests to guarded routes redirect to the correct realm's login form: the PLATFORM
        // admin (/admin*) bounces to the admin login, the GUEST portal (/guest/*) to the guest login, everything
        // else (/app, /owner) to the operator/owner login. Realm separation extends to the redirect target — a
        // guest is never sent to the operator login, and a platform admin never to a tenant login.
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return $request->is('guest/*') ? route('guest.login') : route('login');
        });

        // Inbound provider webhooks are stateless and signature-authenticated, not session/form
        // posts — exempt them from CSRF (there is no token to send). The public booking-engine JSON
        // API (machine clients: `/quote`, `/book`) is likewise token-authenticated by contract and
        // stays exempt. The STYLED public checkout form (design pass · Booking Screen 3) is a
        // session, browser-rendered POST — it is deliberately NOT exempt, so it is session-CSRF-
        // protected (it carries @csrf) on top of the group's `throttle:booking` rate limit.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'book/*/quote',
            'book/*/book',
        ]);

        // The trusted-device cookie (Wave B · GATE 4) is a RANDOM bearer token, verified server-side by its
        // SHA-256 hash ({@see App\Support\Auth\TrustedDeviceService}). It is httpOnly + secure and carries no
        // readable data, so it is exempt from cookie encryption (like a remember-token) — encrypting it would
        // add nothing (the value is already unguessable + hash-checked) and only complicate reading it back.
        $middleware->encryptCookies(except: [
            TrustedDeviceService::COOKIE,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
