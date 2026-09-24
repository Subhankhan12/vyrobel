<?php

use App\Exceptions\PlatformWriteRefused;
use App\Http\Middleware\EnterBreakGlassSession;
use App\Http\Middleware\RefuseWritesDuringBreakGlass;
use App\Http\Middleware\RequirePasswordBeforeReauth;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantForWebRequest;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Platform\BreakGlass;
use App\Support\Security\RefusalLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        // ⚠️⚠️ ADMIN 01 · PHASE 3 — EnterBreakGlassSession MUST run BEFORE `auth`, and its place first in the /app
        //    group is NOT enough: 🔬 Laravel SORTS route middleware by this priority list, and `auth` outranks the
        //    web group's SubstituteBindings — so it was moved AHEAD of EnterBreakGlassSession, and a real admin in a
        //    support session was sent to /login before the session was ever read. The suite did not see it at first:
        //    `actingAs($admin, 'admin')` had made `admin` the default guard, so `auth` passed on the admin.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnterBreakGlassSession::class,
        );
        // ⚠️ …and LAYER 1 (the non-GET refusal) must run BEFORE EnterBreakGlassSession, i.e. OUTSIDE the read-only
        //    scope it opens. 🔬 Measured: once EnterBreakGlassSession was pulled forward, the refusal ran INSIDE that
        //    scope, its own audit insert was refused, and the page came back as layer 2's — layer 1 was silently gone.
        //    Applied in order, so this one can name the prepend above.
        $middleware->prependToPriorityList(
            before: EnterBreakGlassSession::class,
            prepend: RefuseWritesDuringBreakGlass::class,
        );

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
            // ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS READ-ONLY, LAYER 1: every non-GET request from a browser holding a
            //    break-glass session is refused, on EVERY web route (Livewire updates included) outside /admin.
            RefuseWritesDuringBreakGlass::class,
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

        // ⚠️⚠️ ADMIN 01 · PHASE 3 — A PAGE THAT TRIED TO WRITE DURING A READ-ONLY BREAK-GLASS SESSION. The
        //    connection refused it (EnterBreakGlassSession runs every break-glass page inside
        //    PlatformWriteGuard::readOnly); this turns that into a plain 403 instead of a 500, and marks the request
        //    so the middleware can audit it. ⚠️ It must NOT write here: this runs INSIDE the read-only scope, so an
        //    audit insert from this callback would itself be refused. ⚠️ Not reported as a server error — it is the
        //    guard working, and the health board's error count must not rise because a support session looked.
        // ⚠️ WALK THE CHAIN: a write attempted from INSIDE a Blade template is re-thrown by Laravel's CompilerEngine
        //    as a ViewException wrapping the refusal (read in vendor, not assumed), and a callback typed on
        //    PlatformWriteRefused would not match it — that page would 500. A write from a component's PHP (the
        //    maintenance pages' first-view settings row) arrives unwrapped; both are covered and both are tested.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->attributes->has(BreakGlass::ATTRIBUTE) || ! BreakGlass::isWriteRefusal($e)) {
                return null;
            }
            $request->attributes->set(BreakGlass::WRITE_REFUSED, true);

            return response()->view('errors.layout', [
                'code' => 403,
                'title' => 'Read-only support session',
                'message' => 'This page tried to save something, and a Vyrobel support session cannot change anything — so it was stopped.',
            ], 403);
        });
        $exceptions->dontReportWhen(
            fn (Throwable $e): bool => request()->attributes->has(BreakGlass::ATTRIBUTE) && BreakGlass::isWriteRefusal($e),
        );

        // ⚠️⚠️ THE REFUSAL LOG — see {@see \App\Support\Security\RefusalLog} for the full argument.
        //
        // ⚠️⚠️ THESE CALLBACKS RETURN `null` ON PURPOSE. A render callback that returns null falls through to
        // the framework's normal rendering, so the response stays **byte-identical** — which is the entire
        // design. 🔬 Verified by measurement, not by reading: the 404 body is 5,653 bytes before and after.
        // ⚠️ Making "not yours" look different from "not found" would hand an attacker an existence oracle;
        // the attribution belongs in a channel the public cannot see, and this is that channel.
        //
        // ⚠️ `render` rather than `report` is deliberate: Laravel's internal don't-report list already
        // contains both of these, so a `report()` callback would never fire without `stopIgnoring()`, and
        // that would drag full stack traces into the log for every ordinary 404. This hook logs one line.
        // ⚠️⚠️ TYPED ON THE *HTTP* EXCEPTION, NOT THE ORIGINAL — AND THAT COST A MEASUREMENT TO LEARN.
        // 🔬 The first version hooked `ModelNotFoundException` and `AuthorizationException` directly and
        // logged NOTHING: `Handler::render()` calls `prepareException()` BEFORE `renderViaCallbacks()`, so by
        // the time a callback runs the exception has already been mapped to `NotFoundHttpException` /
        // `AccessDeniedHttpException`. ⚠️ The original survives only as `$previous` — which is what this
        // unwraps. An empty log is not a result; it was read as one for about a minute.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $original = $e->getPrevious();

            if ($original instanceof ModelNotFoundException) {
                RefusalLog::modelNotFound($original, $request);
            } elseif ($original instanceof AuthorizationException) {
                RefusalLog::authorizationDenied($original, $request);
            } elseif ($original === null && in_array($e->getStatusCode(), [403, 404], true)) {
                // ⚠️ A deliberate abort(403|404): HttpException with NO $previous. 🔬 44 such sites exist and
                //    the first version of this log was blind to all of them. RefusalLog itself requires a
                //    MATCHED route, so an unrouted 404 still writes nothing.
                RefusalLog::deliberateAbort($e->getStatusCode(), $request);
            }

            return null; // fall through to the framework's own page — response byte-identical
        });

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
