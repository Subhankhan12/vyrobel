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
