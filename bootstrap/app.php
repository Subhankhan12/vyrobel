<?php

use App\Http\Middleware\RequirePasswordBeforeReauth;
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
    // ⚠️⚠️ EVENT DISCOVERY OFF — EXPLICIT REGISTRATION IS THE ONE MECHANISM (F-40, FIX 19, 2026-09-14).
    //
    // Laravel 13 ships `EventServiceProvider::$shouldDiscoverEvents = true`, so every class in `app/Listeners`
    // was ALSO registered automatically as `Class@handle` — on top of the explicit `Event::listen` calls in
    // AppServiceProvider. 🔬 MEASURED: 14 registrations on `DomainEventRecorded` where 7 were intended, plus
    // a duplicate on `AccessTokenCreated`. Every listener ran TWICE.
    //
    // ⚠️ WHY EXPLICIT WINS, AND IT IS NOT A PREFERENCE: **ORDER IS LOAD-BEARING AND DISCOVERY CANNOT EXPRESS
    // IT.** The money listeners are registered before the best-effort ones because a ledger debit was once
    // LOST behind a notification that threw (see docs/POST-COMMIT-TRAIL.md). Discovery registers in whatever
    // order the filesystem yields: 🔬 in the discovered set `DispatchNotificationsForDomainEvent` preceded
    // `AdjustOwnerLedgerOnRefund` — the exact arrangement the ordering fix was written to prevent.
    //
    // ⚠️⚠️ CHECKED BEFORE FLIPPING IT, because unregistering a listener nobody had listed would be far worse
    // than the duplicate: 🔬 **every discovered listener also had an explicit registration — DISCOVERED-ONLY
    // was ZERO.** Nothing loses its registration here; 23 explicit-only registrations are untouched.
    //
    // ⚠️ AND THE DUPLICATE WAS NOT HARMLESS. `AuditOAuthTokenGranted` has no idempotence guard at all, so a
    // single `AccessTokenCreated` wrote TWO audit rows — 🔬 proven by dispatching the event in a rolled-back
    // transaction. The other seven listeners survived only because each carries its own guard, which is
    // idempotence doing the ordering's job.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        // Resolve the active organization from the authenticated user on every web request,
        // syncing TenantContext + spatie team id. CLI/queue/tests use programmatic context.
        $middleware->web(append: [
            // ⚠️⚠️ THE PASSWORDLESS ESCAPE — a STEP BEFORE the twenty-four `current_password` rules, never a
            //    bypass around them. It only ever REDIRECTS: a user with no password who reaches a
            //    confirmation is sent to set one and brought back; everyone else falls straight through to
            //    the rule that was always going to run. See RequirePasswordBeforeReauth.
            RequirePasswordBeforeReauth::class,
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

        /*
         * ⚠️ TRUSTED HOSTS — DEFENCE IN DEPTH, NOT THE FIX (audit 2026-08-29 · H-1).
         *
         * The fix is `URL::forceRootUrl()` in AppServiceProvider: it makes every generated url independent
         * of the request, so a poisoned `Host` can no longer reach an emailed token. This adds the second
         * half — a request arriving with an UNEXPECTED host is REFUSED rather than silently served, which is
         * also how a misconfigured proxy announces itself instead of failing quietly.
         *
         * ⚠️ Derived from APP_URL, so there is ONE definition of "our host" (§N.8f) and no second list to
         * drift. `subdomains: false` because this app does no subdomain routing (verified: zero
         * `Route::domain`, and `organizations.custom_domain` has no reader).
         *
         * ⚠️ FAILS TOWARDS TODAY'S BEHAVIOUR, NOT TOWARDS A DEAD SITE: with no usable APP_URL host this
         * returns [], Laravel filters it out, and no host restriction is applied — the same posture as
         * before this line. A blank config must not take the site down.
         *
         * ⚠️ INERT IN `local` AND IN TESTS by the framework's own rule
         * (TrustHosts::shouldSpecifyTrustedHosts()), so it cannot be proven by the suite — see
         * HostHeaderPoisoningTest, which asserts the registration and proves the ROOT-URL half instead.
         */
        $middleware->trustHosts(at: static function (): array {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);

            return is_string($host) && $host !== '' ? [$host] : [];
        }, subdomains: false);

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
        // The marketing unsubscribe POST is the RFC 8058 `List-Unsubscribe-Post` one-click target: an inbox
        // provider posts to it from outside any browser session, so there is no CSRF token to send. The native
        // HMAC `signed` middleware IS the authorisation, and the action is idempotent and self-only (it can only
        // ever clear ONE guest's marketing consent), so a forged post achieves nothing an attacker wants.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'book/*/quote',
            'book/*/book',
            'unsubscribe/*',
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
