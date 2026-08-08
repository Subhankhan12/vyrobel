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

        // Security response headers on EVERY response, all five realms (audit 2026-08-07 — the app sent none
        // at all, so every money surface was framable). The policy is built from an inventory of what the app
        // really loads; see the class docblock.
        //
        // ⚠️ GLOBAL, not `web(prepend:)`. Group middleware only runs once a route has MATCHED, so an
        // unmatched-route 404 — including the branded error page — came back with ZERO headers while every
        // real page had five. Measured during the final sweep. Global placement also covers the responses a
        // short-circuit produces: a 403 from a permission gate, a redirect from an auth guard, a 429 from the
        // login throttle. Non-document responses (PDF/CSV streams) are skipped inside the middleware itself.
        $middleware->prepend(SecurityHeaders::class);

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

        // ⚠️ NEVER FLASH A SECRET INTO THE SESSION (audit 2026-08-07 · F6-3).
        //
        // On a validation failure Laravel flashes ALL old input so the form can be repopulated. The app
        // defined no exclusions, so the framework default (password fields only) applied — and the owner
        // payout form's IBAN and account-holder name were written in PLAINTEXT to the `sessions` table.
        // Reproduced against the raw row before the fix:
        //     "account_holder":"Ada Lovelace SecretHolder","iban":"PT50000000000000000000999"
        // `payout_methods.iban` is an ENCRYPTED column precisely because an IBAN is sensitive; flashing it
        // in the clear defeats that control entirely, and the session store is not encrypted
        // (SESSION_ENCRYPT=false, correct for a database driver holding only an id — but not for this).
        // It is the codebase's own rule, one layer over: an encrypted column is undone the moment its value
        // is copied somewhere plaintext.
        //
        // Declared GLOBALLY rather than per-controller so it covers every form that uses these field names —
        // including ones not yet written. The cost is that a rejected payout form no longer repopulates the
        // IBAN; retyping it is the correct trade for not storing it in the clear.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            // Owner payout / banking (the reported leak).
            'iban',
            'account_holder',
            'account_number',
            'routing_number',
            'swift',
            'bic',
            // Tax identifiers — personal data in most jurisdictions.
            'tax_id',
            'vat_id',
            'vat_number',
            // Card data never reaches the server (SAQ A / hosted Checkout), but if a field is ever added,
            // it must not be the flash that first persists it.
            'card_number',
            'cvc',
            'cvv',
            // Credentials for the platform integrations + the 2FA/recovery flows.
            'api_key',
            'secret',
            'client_secret',
            'webhook_secret',
            'two_factor_code',
            'recovery_code',
        ]);
    })->create();
