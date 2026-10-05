<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Providers\AppServiceProvider;

/**
 * THE #1 production-email guard (Phase 8) — a real transactional email must NEVER be sent to a real person
 * from a non-production environment. A test suite (or a careless dev `.env`) that reached the live provider
 * would blast real guests/owners: a real-world incident.
 *
 * The separation is enforced in CODE, not just by convention: {@see safeMailerFor()} refuses every
 * REAL-SENDING transport outside `production` and downgrades it to `log`. Applied at boot
 * ({@see AppServiceProvider}), it overrides `mail.default` so even
 * `MAIL_MAILER=postmark` in a local/CI env silently logs instead of delivering. Only `production` may use a
 * sending provider.
 *
 * SAFE (non-sending / local-only) transports are left untouched: `log`, `array` (the test transport), and `smtp`
 * pointed at THIS machine (Mailpit → 127.0.0.1:1025 in dev). `sendmail` is treated as SENDING (it can deliver to real MX).
 *
 * ⚠️⚠️ MAIL 02 (2026-09-25) — `smtp` IS ALSO A REAL PROVIDER NOW. The founder chose Hostinger's own mail
 * (`smtp.hostinger.com`) over Postmark, and until today `smtp` meant only Mailpit here — so a developer's `.env`
 * holding the Hostinger mailbox credentials would have emailed real guests from a laptop, and this guard would have
 * waved it through. `smtp` now counts as SENDING unless its host is a LOCAL one (loopback / `mailpit`).
 */
final class MailSafetyGuard
{
    /** Transports that can deliver to a REAL inbox — forbidden outside production. */
    private const SENDING = ['postmark', 'ses', 'ses-v2', 'resend', 'mailgun', 'sendmail'];

    /** SMTP hosts that are THIS machine — a local catch-inbox (Mailpit), never a real person. */
    private const LOCAL_SMTP_HOSTS = ['', '127.0.0.1', 'localhost', '::1', '0.0.0.0', 'mailpit'];

    /**
     * The mailer that is SAFE to use for the given environment. Production keeps whatever is configured; every
     * other environment has a real-sending transport downgraded to `log` (Mailpit `smtp` / `log` / `array`
     * stay as-is — they never reach a real person).
     */
    public static function safeMailerFor(string $environment, string $configured, ?string $smtpHost = null): string
    {
        if ($environment === 'production') {
            return $configured;
        }

        return self::isSending($configured, $smtpHost) ? 'log' : $configured;
    }

    /** Whether a transport delivers to real inboxes (used for logging/telemetry). */
    public static function isSending(string $mailer, ?string $smtpHost = null): bool
    {
        if ($mailer === 'smtp') {
            return ! self::isLocalSmtpHost($smtpHost);
        }

        return in_array($mailer, self::SENDING, true);
    }

    /**
     * Whether email from this app reaches real inboxes RIGHT NOW — the configured mailer as {@see isSending()} reads it. ⚠️ The ONE
     * question every screen asks before it says "sent by email" (2026-10-05: the Guests page's Message said so while production's
     * mailer was `log`, and recorded a sent message in each guest's inbox; the New reservation page asked the same thing inline).
     */
    public static function sendingNow(): bool
    {
        $host = config('mail.mailers.smtp.host');

        return self::isSending((string) config('mail.default'), is_string($host) ? $host : null);
    }

    /** Whether an SMTP host is THIS machine (a local catch-inbox) rather than a real mail provider. */
    public static function isLocalSmtpHost(?string $host): bool
    {
        return in_array(strtolower(trim((string) $host)), self::LOCAL_SMTP_HOSTS, true);
    }
}
