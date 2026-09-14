<?php

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · H-1 — A POISONED HOST PUT THE ATTACKER'S DOMAIN IN THE VICTIM'S EMAIL ═════════
//
// ⚠️ `route()`/`url()` build an ABSOLUTE url from the REQUEST host unless a root url is forced, and the
// reset mail is rendered SYNCHRONOUSLY INSIDE the request — so the emailed link, carrying a valid reset
// TOKEN, pointed at whatever host the attacker supplied. REPRODUCED against the running app, both ways:
//     Host: attacker.example           → http://attacker.example/reset-password/<valid token>
//     X-Forwarded-Host: evil.example   → http://evil.example/reset-password/<valid token>
//       (the second under TRUSTED_PROXIES=*, which config/security.php recommends behind a load balancer)
//
// ⚠️ THE FIX IS `URL::forceRootUrl(config('app.url'))` — every generated url becomes independent of the
// request. `trustHosts()` is the second half and NOT the fix: trusted hosts bound WHICH hosts may be
// supplied, but the request would still choose among them.
//
// ⚠️ A NOTE ON PROVING THIS, BECAUSE THE OBVIOUS TEST IS A FALSE GREEN. Laravel's test client builds its
// request from APP_URL and IGNORES a `Host` HEADER — a first attempt at this test passed identically with
// and without the fix. The request URI must be ABSOLUTE for the host to take effect. Test 1 pins that the
// test is SENSITIVE by un-forcing the root url and showing the poison land; without it the rest could all
// pass for the wrong reason.

/** The host of the reset link in the mail that was just sent. */
function resetLinkHost(): ?string
{
    $host = null;

    Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$host): bool {
        preg_match('~https?://([^/"\'\s]+)/reset-password~', $mail->render(), $m);
        $host = $m[1] ?? null;

        return true;
    });

    return $host;
}

/** Host WITHOUT port — for comparing against parse_url(..., PHP_URL_HOST). */
function configuredHost(): string
{
    return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
}

/** Host WITH port — what actually appears in a rendered link (parse_url drops the port). */
function configuredAuthority(): string
{
    $parts = parse_url((string) config('app.url'));
    $host = (string) ($parts['host'] ?? '');

    return isset($parts['port']) ? $host.':'.$parts['port'] : $host;
}

// ── 1. ⚠️ THE TEST IS SENSITIVE — with the root url un-forced, the poison lands ─────────────────────
// This is the vulnerability itself, reproduced in-suite. If this ever stops failing to pin the attacker's
// host, the harness has stopped exercising the mechanism and every other test here is worthless.
it('confirms the poison DOES land when the root url is not forced', function () {
    URL::forceRootUrl(null);                       // the pre-fix state, for this test only
    Mail::fake();
    User::factory()->create(['email' => 'sensitive@example.test']);

    $this->post('http://attacker.example/forgot-password', ['email' => 'sensitive@example.test']);

    expect(resetLinkHost())->toBe('attacker.example');
});

// ── 2. ⛔ THE FIX — a poisoned host cannot reach the emailed reset token ────────────────────────────
it('builds the password-reset link from config, not the poisoned request host', function () {
    Mail::fake();
    User::factory()->create(['email' => 'victim@example.test']);

    $this->post('http://attacker.example/forgot-password', ['email' => 'victim@example.test']);

    expect(resetLinkHost())
        ->toBe(configuredAuthority())
        ->not->toBe('attacker.example');
});

// ── 3. ⛔ THE MECHANISM, NOT JUST ONE MAILABLE ──────────────────────────────────────────────────────
// Enumerating mailables would leave the next one unprotected. These are the two generators EVERY
// token-carrying link uses — plain `route()` (user + guest reset, both invite links) and
// `temporarySignedRoute()` (login approval, guest claim, both email-change confirmations).
it('pins the host for every url generator under a poisoned request', function () {
    $this->get('http://attacker.example/login');   // put a poisoned request in flight

    $plain = route('password.request');
    $signed = URL::temporarySignedRoute('login-approval.approve', now()->addMinutes(30), ['approval' => '01ABC']);

    expect(parse_url($plain, PHP_URL_HOST))->toBe(configuredHost())
        ->and(parse_url($signed, PHP_URL_HOST))->toBe(configuredHost());

    expect($plain)->not->toContain('attacker.example');
    expect($signed)->not->toContain('attacker.example');
});

// ── 4. defence in depth — trusted hosts are pinned to the configured host ───────────────────────────
// ⚠️ HONEST SCOPE: TrustHosts::shouldSpecifyTrustedHosts() is FALSE in `local` and under tests, so the
// suite CANNOT observe a request being refused. What is asserted here is that the middleware is
// registered and resolves to the configured host — the registration, not the refusal. The refusal is
// verified by the runbook step (a curl with a wrong Host) in DEPLOYMENT.md §7.7.
it('pins trusted hosts to the configured application host', function () {
    $hosts = app(TrustHosts::class)->hosts();

    expect($hosts)->toContain(configuredHost())
        ->and($hosts)->not->toContain('attacker.example');
});
