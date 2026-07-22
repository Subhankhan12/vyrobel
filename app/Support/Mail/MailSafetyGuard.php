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
 * SAFE (non-sending / local-only) transports are left untouched: `log`, `array` (the test transport),
 * `smtp` (Mailpit → 127.0.0.1:1025 in dev), `sendmail` is treated as SENDING (it can deliver to real MX).
 */
final class MailSafetyGuard
{
    /** Transports that can deliver to a REAL inbox — forbidden outside production. */
    private const SENDING = ['postmark', 'ses', 'resend', 'mailgun', 'sendmail'];

    /**
     * The mailer that is SAFE to use for the given environment. Production keeps whatever is configured; every
     * other environment has a real-sending transport downgraded to `log` (Mailpit `smtp` / `log` / `array`
     * stay as-is — they never reach a real person).
     */
    public static function safeMailerFor(string $environment, string $configured): string
    {
        if ($environment === 'production') {
            return $configured;
        }

        return in_array($configured, self::SENDING, true) ? 'log' : $configured;
    }

    /** Whether a transport delivers to real inboxes (used for logging/telemetry). */
    public static function isSending(string $mailer): bool
    {
        return in_array($mailer, self::SENDING, true);
    }
}
