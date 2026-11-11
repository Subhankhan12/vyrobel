<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;

if (! function_exists('emailIsBeingSent')) {
    /**
     * Make the app say "email IS sent from Vyrobel" for the rest of this test — and capture what would go out.
     *
     * ⚠️ D-MAIL-CLAIMS (2026-11-11): screens now say an email was sent only while `MailSafetyGuard::sendingNow()` is true, and the suite's mailer is
     * `array` (never sending). A test of a "sent" path sets this first. The mailer is a real provider's NAME set AFTER boot (the boot guard downgrades
     * one outside production), and `Mail::fake()` goes with it, so nothing can reach a provider — a faked mailer never builds a transport.
     */
    function emailIsBeingSent(): void
    {
        config(['mail.default' => 'postmark', 'mail.mailers.postmark' => ['transport' => 'postmark']]);
        Mail::fake();
    }
}
