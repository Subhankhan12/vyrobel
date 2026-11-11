<?php

use App\Support\Mail\MailSafetyGuard;

// PHASE 8 · production email — THE #1 GUARD: a real transactional email must NEVER be sent from a
// non-production environment. The separation is enforced in CODE (MailSafetyGuard), proven here.

// ── 1. 🔒 THE RUNNING TEST SUITE USES A NON-SENDING TRANSPORT — it can't email a real person ─────────
it('runs the test suite on a non-sending mail transport', function () {
    // phpunit.xml sets MAIL_MAILER=array; the boot-time guard would downgrade any real provider to `log`
    // anyway. Either way, the transport the suite actually uses does NOT deliver to a real inbox.
    $active = (string) config('mail.default');
    expect($active)->toBe('array');
    expect(MailSafetyGuard::isSending($active))->toBeFalse();
});

// ── 2. 🔒 THE GUARD DOWNGRADES A REAL PROVIDER OUTSIDE PRODUCTION, KEEPS IT IN PRODUCTION ────────────
it('refuses a real-sending transport outside production and allows it in production', function () {
    // Non-production → every real-sending provider becomes `log` (can't reach a real person).
    expect(MailSafetyGuard::safeMailerFor('testing', 'postmark'))->toBe('log');
    expect(MailSafetyGuard::safeMailerFor('local', 'postmark'))->toBe('log');
    expect(MailSafetyGuard::safeMailerFor('ci', 'ses'))->toBe('log');
    expect(MailSafetyGuard::safeMailerFor('staging', 'resend'))->toBe('log');

    // Production → the configured provider is honoured (the ONLY place a real send can happen).
    expect(MailSafetyGuard::safeMailerFor('production', 'postmark'))->toBe('postmark');
    expect(MailSafetyGuard::safeMailerFor('production', 'ses'))->toBe('ses');
});

// ── 3. ✅ SAFE (non-sending / local) transports are left untouched in every environment ──────────────
it('leaves log, array and Mailpit smtp untouched', function () {
    foreach (['testing', 'local', 'production'] as $env) {
        expect(MailSafetyGuard::safeMailerFor($env, 'array'))->toBe('array');   // the test transport
        expect(MailSafetyGuard::safeMailerFor($env, 'log'))->toBe('log');       // dev default
        expect(MailSafetyGuard::safeMailerFor($env, 'smtp'))->toBe('smtp');     // Mailpit in dev
    }

    expect(MailSafetyGuard::isSending('array'))->toBeFalse();
    expect(MailSafetyGuard::isSending('log'))->toBeFalse();
    expect(MailSafetyGuard::isSending('smtp'))->toBeFalse();
    expect(MailSafetyGuard::isSending('postmark'))->toBeTrue();
});

// ── ⚠️⚠️ D-MAIL-GUARD-SHAPES (found 2026-10-05 by the Guests review; fixed 2026-11-11, his "do all") ────────────────────────────
// The boot guard read a mailer by its NAME: `failover`, `roundrobin` and a custom-named smtp mailer were not downgraded outside production,
// and an smtp host given only in MAIL_URL read as the default 127.0.0.1 — a developer `.env` shaped that way could email real people from a
// laptop. It now judges the TRANSPORT behind the name, the way Laravel builds the mailer, and fails CLOSED outside production.

/** @return array<string, array<string, mixed>> a mailers config like config/mail.php's, plus the given extras */
function mgsMailers(array $extra = []): array
{
    return $extra + [
        'smtp' => ['transport' => 'smtp', 'url' => null, 'host' => '127.0.0.1', 'port' => 1025],
        'log' => ['transport' => 'log'],
        'array' => ['transport' => 'array'],
        'postmark' => ['transport' => 'postmark'],
        'ses' => ['transport' => 'ses'],
        'real-smtp' => ['transport' => 'smtp', 'host' => 'smtp.hostinger.com', 'port' => 587],
        'mailpit' => ['transport' => 'smtp', 'host' => 'mailpit', 'port' => 1025],
    ];
}

it('⚠️⚠️ a custom-named smtp mailer is judged by ITS host — and only the real one is downgraded', function () {
    $m = mgsMailers();

    expect(MailSafetyGuard::safeMailerFor('local', 'real-smtp', null, $m))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('testing', 'real-smtp', null, $m))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('local', 'mailpit', null, $m))->toBe('mailpit')           // a local catch-inbox, kept
        ->and(MailSafetyGuard::safeMailerFor('production', 'real-smtp', null, $m))->toBe('real-smtp')   // production is never touched
        ->and(MailSafetyGuard::isSending('real-smtp', null, $m))->toBeTrue()
        ->and(MailSafetyGuard::isSending('mailpit', null, $m))->toBeFalse();
})->group('security');

it('⚠️⚠️ MAIL_URL decides the transport: a URL under the mailer named `smtp` is what the URL says, not the host key beside it', function () {
    // 127.0.0.1 in the host key, a REAL host in the URL
    $real = mgsMailers(['smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'url' => 'smtp://user:pw@smtp.hostinger.com:587']]);
    expect(MailSafetyGuard::safeMailerFor('local', 'smtp', '127.0.0.1', $real))->toBe('log');

    // the same mailer, the URL pointing at THIS machine → kept
    $local = mgsMailers(['smtp' => ['transport' => 'smtp', 'host' => 'smtp.hostinger.com', 'url' => 'smtp://localhost:1025']]);
    expect(MailSafetyGuard::safeMailerFor('local', 'smtp', 'smtp.hostinger.com', $local))->toBe('smtp');

    // the URL's SCHEME is the transport (Laravel pulls `driver` into `transport`): `postmark://` under `smtp` sends through Postmark
    $postmark = mgsMailers(['smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'url' => 'postmark://key@default']]);
    expect(MailSafetyGuard::safeMailerFor('local', 'smtp', '127.0.0.1', $postmark))->toBe('log')
        ->and(MailSafetyGuard::isSending('smtp', null, $postmark))->toBeTrue();

    // an unparseable URL cannot be read → fails closed
    $broken = mgsMailers(['smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'url' => 'smtp:///']]);
    expect(MailSafetyGuard::safeMailerFor('local', 'smtp', '127.0.0.1', $broken))->toBe('log');
})->group('security');

it('⚠️⚠️ failover and roundrobin are every mailer in their list — one real child downgrades the whole', function () {
    $m = mgsMailers([
        'fo-safe' => ['transport' => 'failover', 'mailers' => ['smtp', 'log']],
        'fo-real' => ['transport' => 'failover', 'mailers' => ['real-smtp', 'log']],
        'fo-postmark' => ['transport' => 'failover', 'mailers' => ['log', 'postmark']],
        'rr-real' => ['transport' => 'roundrobin', 'mailers' => ['ses', 'postmark']],
    ]);

    expect(MailSafetyGuard::safeMailerFor('local', 'fo-safe', null, $m))->toBe('fo-safe')            // Mailpit + log: nothing real
        ->and(MailSafetyGuard::safeMailerFor('local', 'fo-real', null, $m))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('local', 'fo-postmark', null, $m))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('testing', 'rr-real', null, $m))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('production', 'rr-real', null, $m))->toBe('rr-real');
})->group('security');

it('⚠️⚠️ what cannot be read fails CLOSED outside production — a cycle, an empty list, a missing child, an unknown transport', function () {
    $m = mgsMailers([
        'loop' => ['transport' => 'failover', 'mailers' => ['loop']],              // lists itself: must terminate, not recurse for ever
        'ping' => ['transport' => 'failover', 'mailers' => ['pong']],
        'pong' => ['transport' => 'roundrobin', 'mailers' => ['ping']],
        'empty' => ['transport' => 'failover', 'mailers' => []],
        'orphan' => ['transport' => 'failover', 'mailers' => ['not-defined']],
        'custom' => ['transport' => 'my-own-transport'],
    ]);

    foreach (['loop', 'ping', 'empty', 'orphan', 'custom'] as $name) {
        expect(MailSafetyGuard::safeMailerFor('local', $name, null, $m))->toBe('log', $name)
            ->and(MailSafetyGuard::safeMailerFor('production', $name, null, $m))->toBe($name);   // …and production still keeps what is configured
    }
})->group('security');

it('⚠️⚠️ sendingNow() is CONSERVATIVE — true only when every transport behind the mailer is a real one (a "sent" claim never rests on a maybe)', function () {
    $set = function (string $default, array $mailers): void {
        config(['mail.default' => $default, 'mail.mailers' => $mailers]);
    };
    $m = mgsMailers([
        'fo-real-log' => ['transport' => 'failover', 'mailers' => ['real-smtp', 'log']],
        'fo-two-real' => ['transport' => 'failover', 'mailers' => ['real-smtp', 'postmark']],
        'custom' => ['transport' => 'my-own-transport'],
    ]);

    foreach ([['postmark', true], ['real-smtp', true], ['fo-two-real', true], ['fo-real-log', false], ['log', false], ['array', false], ['mailpit', false], ['smtp', false], ['custom', false]] as [$name, $expect]) {
        $set($name, $m);
        expect(MailSafetyGuard::sendingNow())->toBe($expect, $name);
    }

    // production's shape: the mailer named `smtp` with a real host (Hostinger) → a claim of "sent" is allowed
    $set('smtp', mgsMailers(['smtp' => ['transport' => 'smtp', 'host' => 'smtp.hostinger.com', 'url' => null]]));
    expect(MailSafetyGuard::sendingNow())->toBeTrue();
})->group('security');

it('the name-only calls the older code and tests make are unchanged', function () {
    expect(MailSafetyGuard::safeMailerFor('local', 'whatever-custom'))->toBe('whatever-custom')   // no config to read: not sending, as before
        ->and(MailSafetyGuard::safeMailerFor('local', 'failover'))->toBe('failover')
        ->and(MailSafetyGuard::isSending('failover'))->toBeFalse();
});
