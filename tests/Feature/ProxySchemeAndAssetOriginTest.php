<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * ⚠️⚠️ WHETHER THE APPLICATION KNOWS ITS OWN ADDRESS (docs/PROXY-HOSTS-HTTPS.md).
 *
 * This project has already had the incident once: a port mismatch made `asset()` emit a CROSS-ORIGIN URL,
 * the CSP's `'self'` refused every script, and the whole marketing site's controls looked dead behind ONE
 * console error. Behind a TLS-terminating proxy the same class of misconfiguration is silent and total.
 *
 * ⚠️ NOTHING IS DEPLOYED, so none of this can be proven against a real proxy. What IS proven here is what
 * the application generates and honours when the forwarded headers are forged — both when the proxy is
 * trusted and when it is not. The rest arrives with the server; the first-deploy checklist is in
 * docs/GO-LIVE-REQUIREMENTS.md.
 */

/**
 * ⚠️ A RAW `Request`, NOT `$this->get($uri, ['Host' => …])`, AND THE DIFFERENCE IS NOT COSMETIC.
 *
 * Laravel's test client pins the request host from `APP_URL`: a `Host` header passed to `get()` reaches
 * `request()->header('Host')` but NEVER changes `request()->getHost()`. Measured — every variant
 * (`get($uri, $headers)`, `withHeaders()`, `withServerVariables(['HTTP_HOST' => …])`, `call()` with server
 * parameters) still reported `localhost`. A proxy test written that way asserts nothing at all, and would
 * pass whatever the trust configuration said.
 *
 * ⚠️ The forwarded headers themselves DO arrive — `X-Forwarded-Proto` is readable on the request and is
 * simply ignored while no proxy is trusted, which is the safe default working.
 */
function pxRequest(array $server = []): Request
{
    return Request::create('http://internal-app:8080/some/path', 'GET', [], [], [], $server + [
        'REMOTE_ADDR' => '10.0.0.9',
    ]);
}

/**
 * Run a request through the application's REAL `TrustProxies` middleware.
 *
 * ⚠️⚠️ THE FIRST VERSION OF THIS HELPER CALLED `Request::setTrustedProxies()` WITH ITS OWN HEADER MASK,
 * AND THAT MADE IT USELESS. Supplying the mask in the test means the test proves the test: a mutation
 * removing `HEADER_X_FORWARDED_PROTO` from the application's own mask in `AppServiceProvider` came back
 * GREEN, because nothing here ever consulted it. The proxy LIST is the only thing a test may supply — the
 * MASK must come from the app, which is what `TrustProxies::at()` leaves in place.
 */
/** @return array{secure: bool, host: string, trusted_header_set: int} */
function pxThroughMiddleware(string $proxies, Request $request): array
{
    $originalProxies = Request::getTrustedProxies();
    $originalHeaders = Request::getTrustedHeaderSet();

    // ⚠️⚠️ THE APPLICATION'S OWN CONFIGURATION IS WHAT RUNS. `AppServiceProvider::boot()` only calls
    //    `TrustProxies::at()`/`withHeaders()` when `security.trusted_proxies` is non-empty — so the proxy
    //    list is set through CONFIG and the provider is re-booted, rather than the test calling the
    //    statics itself. Supplying the header mask here is what made the first version of this helper
    //    useless: a mutation deleting `HEADER_X_FORWARDED_PROTO` from the app's mask came back GREEN.
    config(['security.trusted_proxies' => $proxies]);
    app()->register(AppServiceProvider::class, true);

    try {
        app(TrustProxies::class)->handle($request, fn (Request $r): Response => new Response);

        // ⚠️⚠️ THE FACTS ARE READ *INSIDE* THIS SCOPE, AND THAT IS NOT TIDINESS. `isSecure()` and
        //    `getHost()` re-evaluate against the CURRENT static trusted-proxy state every time they are
        //    called — so restoring that state in the `finally` below and asserting afterwards read
        //    `false` no matter what the middleware had done. Measured: the same request reported
        //    `isSecure() === true` before the reset and `false` after it.
        return [
            'secure' => $request->isSecure(),
            'host' => $request->getHost(),
            'trusted_header_set' => Request::getTrustedHeaderSet(),
        ];
    } finally {
        config(['security.trusted_proxies' => '']);
        Request::setTrustedProxies($originalProxies, $originalHeaders);
    }
}

// ══ Forged forwarded headers — BOTH halves ══════════════════════════════════════════════════════════

it('⚠️⚠️ IGNORES a forged X-Forwarded-Proto when the sender is NOT a trusted proxy', function () {
    $request = pxRequest([
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'vyrobel.example',
        'HTTP_X_FORWARDED_PORT' => '443',
    ]);

    // The headers are present and readable — they are simply not believed.
    expect($request->header('X-Forwarded-Proto'))->toBe('https')
        ->and($request->isSecure())->toBeFalse()
        ->and($request->getHost())->toBe('internal-app');
});

it('⚠️⚠️ HONOURS the same headers when the sender IS a trusted proxy', function () {
    $request = pxRequest([
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'vyrobel.example',
        'HTTP_X_FORWARDED_PORT' => '443',
    ]);

    $seen = pxThroughMiddleware('10.0.0.9', $request);

    expect($seen['secure'])->toBeTrue()
        ->and($seen['host'])->toBe('vyrobel.example');

    // ⚠️ And the mask the APPLICATION installed really carries the proto bit — asserted directly, because
    //    `isSecure()` alone could be satisfied by a framework default rather than by this app's choice.
    expect($seen['trusted_header_set'] & Request::HEADER_X_FORWARDED_PROTO)
        ->not->toBe(0, "the application's trusted-header mask does not include X-Forwarded-Proto");
});

it('⚠️ a trusted proxy list that does NOT include the sender still ignores the headers', function () {
    $request = pxRequest(['HTTP_X_FORWARDED_PROTO' => 'https']);

    // ⚠️ The direction that matters: naming *a* proxy is not naming *this* one.
    expect(pxThroughMiddleware('192.0.2.1', $request)['secure'])->toBeFalse();
});

it('⚠️ the application reads its trusted proxies from configuration, not from a literal', function () {
    // ⚠️ `TRUSTED_PROXIES` is a founder-set go-live value (B14). Empty means trust nothing, which is the
    //    safe default and the one that ships today.
    expect(config('security.trusted_proxies'))->toBe('')
        ->and(config('security.force_https'))->toBeFalse();   // local development, unforced
});

// ══ ⚠️⚠️ THE GUARD THAT IS WORTH MORE THAN THE FIX ══════════════════════════════════════════════════

it('⚠️⚠️ keeps asset() SAME-ORIGIN with the application, because the CSP allows only \'self\'', function () {
    // ⚠️ THIS IS THE INCIDENT, HELD AS A RULE. A port mismatch once made `asset()` cross-origin; the CSP's
    //    `default-src 'self'` refused every script and the marketing site's controls looked dead behind a
    //    single console error. The policy names NO host — it is `'self'` throughout — so the only thing
    //    that can break it is the app emitting an asset URL from a different origin than the document.
    $appOrigin = fn (string $url): string => (string) parse_url($url, PHP_URL_SCHEME)
        .'://'.(string) parse_url($url, PHP_URL_HOST)
        .(parse_url($url, PHP_URL_PORT) !== null ? ':'.parse_url($url, PHP_URL_PORT) : '');

    expect($appOrigin(asset('/build/app.js')))->toBe($appOrigin(url('/anything')));
});

it('⚠️⚠️ refuses to let ASSET_URL point at a foreign origin — the exact shape of the incident', function () {
    // ⚠️ `app.asset_url` is unset today, so `asset()` follows the root url. If it is ever set to another
    //    origin, EVERY script and stylesheet becomes cross-origin and the CSP kills the page silently.
    //    Nothing in Laravel prevents that; this does.
    $assetUrl = config('app.asset_url');

    if ($assetUrl === null || $assetUrl === '') {
        expect(true)->toBeTrue();   // unset — assets follow the app's own origin by construction

        return;
    }

    $origin = fn (?string $url): string => (string) parse_url((string) $url, PHP_URL_SCHEME)
        .'://'.(string) parse_url((string) $url, PHP_URL_HOST)
        .(parse_url((string) $url, PHP_URL_PORT) !== null ? ':'.parse_url((string) $url, PHP_URL_PORT) : '');

    expect($origin($assetUrl))->toBe($origin(config('app.url')),
        'ASSET_URL is a different origin from APP_URL — the CSP allows only \'self\', so every asset would be refused');
});

it('⚠️ the CSP still names no external origin, so \'self\' really is the whole allowance', function () {
    $csp = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("script-src 'self'");

    // ⚠️ `str_contains(...)->toBeFalse()`, never `->not->toContain($needle, $message)` — a negated
    //    multi-argument matcher is unconditionally true, and this suite has a guard for that trap.
    expect(str_contains((string) $csp, 'http://'))
        ->toBeFalse('the policy names an explicit http origin, which asset() must then match exactly');
});

// ══ Signed URLs — the part that fails invisibly ═════════════════════════════════════════════════════

it('⚠️⚠️ a signed link built for a DIFFERENT root does not verify here', function () {
    // ⚠️ This is why `APP_URL` must be the exact canonical origin: the signature covers the absolute URL,
    //    so a link generated under one host is refused under another — and the visitor sees only
    //    "this link is invalid", indistinguishable from one that genuinely expired.
    $ours = URL::signedRoute('marketing.unsubscribe', ['guest' => '01ABCPROXY']);
    $path = (string) parse_url($ours, PHP_URL_PATH);

    URL::forceRootUrl('https://not-our-host.example');
    $foreign = URL::signedRoute('marketing.unsubscribe', ['guest' => '01ABCPROXY']);
    URL::forceRootUrl((string) config('app.url'));

    expect($this->get($path.'?'.parse_url($ours, PHP_URL_QUERY))->status())->toBe(200)
        ->and($this->get($path.'?'.parse_url($foreign, PHP_URL_QUERY))->status())->toBe(403);
});

it('⚠️ generation and verification agree because BOTH go through the forced root url', function () {
    // ⚠️ A queued job has no request, so it generates from `app.url`; a web request verifies against the
    //    same forced root. That is what makes an emailed link work — and why a wrong `APP_URL` breaks
    //    every one of them at once rather than some of them.
    expect(config('app.url'))->toBe(url(''));
});

// ══ Local development must not break ═══════════════════════════════════════════════════════════════

it('⚠️⚠️ LOCAL DEVELOPMENT still generates plain http on localhost', function () {
    // Every browser probe this project relies on runs against exactly this.
    expect(url('/x'))->toStartWith('http://localhost')
        ->and(asset('/x'))->toStartWith('http://localhost')
        ->and(config('security.force_https'))->toBeFalse();

    $this->get('/login')->assertOk();
});

it('⚠️ with no forwarded headers at all — the local case — nothing is inferred', function () {
    $request = Request::create('http://localhost:8000/x', 'GET');

    expect($request->isSecure())->toBeFalse()
        ->and($request->getHost())->toBe('localhost');
});
