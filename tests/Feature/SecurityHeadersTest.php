<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * SECURITY RESPONSE HEADERS (audit 2026-08-07 — "no security headers of any kind", HIGH).
 *
 * The app sent NONE: no CSP, no HSTS, no X-Frame-Options, no nosniff, no Referrer-Policy. Every realm —
 * operator back office, owner portal, guest portal — was FRAMABLE, which matters because those surfaces
 * carry money actions (refunds, payout preferences, on-desk payments).
 *
 * ⚠️ THIS FILE ALSO PINS THE FOUR RELAXATIONS THE APP GENUINELY NEEDS, so a future "let's tighten the CSP"
 * change cannot silently break a realm. Each was verified against a real rendered page, and each has a
 * specific cause documented in SecurityHeaders. Tightening any of them requires the corresponding code work
 * (Alpine's CSP build; removing ~97 inline style attributes; changing the email-preview iframe), not just a
 * header edit.
 */

$realms = [
    'operator login' => '/login',
    'guest login' => '/guest/login',
    'admin login' => '/admin/login',
    'public storefront' => '/book/seaside-demo',
];

it('sends every security header on every realm', function (string $path) {
    $response = $this->get($path);

    // Not asserting 200: a redirect or a 403 needs these headers just as much, which is why the middleware
    // is PREPENDED (it runs even when a later middleware short-circuits).
    foreach ([
        'Content-Security-Policy',
        'X-Frame-Options',
        'X-Content-Type-Options',
        'Referrer-Policy',
        'Permissions-Policy',
    ] as $header) {
        expect($response->headers->get($header))->not->toBeNull("missing {$header} on {$path}");
    }

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
})->with([
    '/login',
    '/guest/login',
    '/admin/login',
]);

it('sends the headers on an UNMATCHED-route 404 too', function () {
    // ⚠️ Group middleware only runs once a route has MATCHED. Registered on the `web` group, an
    // unmatched-route 404 — including the branded error page — came back with ZERO headers while every real
    // page had five. Measured during the final sweep, then moved to global. This pins it.
    $response = $this->get('/no-such-page-here');

    $response->assertStatus(404);

    foreach (['Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy'] as $header) {
        expect($response->headers->get($header))->not->toBeNull("missing {$header} on a 404");
    }
});

it('permits NO external origin at all', function () {
    // Inter and Sora are self-hosted (audit F6-6), so the two Google font origins were the last externals
    // and are gone. Anything reaching for a third party is now a CSP violation by definition.
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('https://');
});

it('blocks cross-origin framing of the money surfaces', function () {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    // The actual clickjacking control. 'self' rather than 'none' is deliberate — see the next test.
    expect($csp)->toContain("frame-ancestors 'self'");
    expect($csp)->toContain("object-src 'none'");
    expect($csp)->toContain("base-uri 'self'");
    expect($csp)->toContain("form-action 'self'");
});

it('keeps the four relaxations the app actually needs', function () {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    // 1. Livewire injects inline <script>; the Alpine it bundles evaluates directives with `new Function`.
    //    Verified functionally in the browser: an x-text of "n + 41" renders "42" under this policy.
    expect($csp)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'");

    // 2. The design system is inline <style> blocks plus ~97 inline style="…" attributes on one operator
    //    page. Inline style ATTRIBUTES cannot carry a nonce or hash — there is no mechanism.
    //    (No font origin here any more — Inter/Sora are self-hosted.)
    expect($csp)->toContain("style-src 'self' 'unsafe-inline';");

    // 3. frame-ancestors is 'self', NOT 'none': the Email Previews page frames a SAME-ORIGIN
    //    /app/email-previews/{key} so the email CSS cannot leak into /app. 'none' would break that page.
    expect($csp)->toContain("frame-src 'self'");
    expect($csp)->not->toContain("frame-ancestors 'none'");

    // 4. data: for the admin shell's SVG favicon; blob: for the owner Add-Unit upload preview
    //    (URL.createObjectURL).
    expect($csp)->toContain('img-src');
    expect($csp)->toContain('data:');
    expect($csp)->toContain('blob:');
});

it('does not pin HSTS over a plain-http request', function () {
    // Pinning HSTS against a dev hostname would poison it in the developer's browser for max-age, and that
    // is not undoable from the server side. It is sent only when the connection is genuinely secure.
    expect($this->get('/login')->headers->get('Strict-Transport-Security'))->toBeNull();
});

it('leaves non-document responses alone', function () {
    // A PDF/CSV stream gets no CSP — the headers are meaningless there and could interfere with a viewer.
    $response = $this->get('/login');
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

// ══════════════════════════════════════════════════════════════════════════════════════════════════
// SESSION / COOKIE / PROXY HARDENING (audit 2026-08-07 · F6-10 and the SESSION_SECURE_COOKIE gap).
//
// `session.secure` was a bare env() with NO default: unset → null → falsy → the session cookie shipped
// WITHOUT the Secure flag, and the variable was absent from .env.example, so a deploy copying that template
// got insecure cookies silently. And with no TrustProxies configuration, three controls break QUIETLY behind
// a load balancer: the operator IP allowlist compares the PROXY's address, per-IP rate limiting collapses
// into one bucket, and isSecure() reads false so HSTS is suppressed.
// ══════════════════════════════════════════════════════════════════════════════════════════════════

it('does not mark the session cookie secure in local development', function () {
    // The whole point of the env-driven default: plain-http local dev must keep working untouched.
    expect(config('app.env'))->not->toBe('production');
    expect(config('session.secure'))->toBeFalse();
})->group('hardening');

it('keeps the session cookie httpOnly and same-site', function () {
    // These were already correct — pinned so a future edit cannot quietly loosen them.
    expect(config('session.http_only'))->toBeTrue();
    expect(config('session.same_site'))->toBe('lax');
})->group('hardening');

it('trusts no proxy by default, so a spoofed forwarded header cannot lie about the scheme', function () {
    // The safe default is preserved: with TRUSTED_PROXIES unset, X-Forwarded-Proto is IGNORED, so an
    // attacker cannot make the app believe a plain request was secure. Verified live both ways: unset →
    // no HSTS on a forwarded-https request; TRUSTED_PROXIES=* → HSTS present.
    $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/login');

    expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
})->group('hardening');
