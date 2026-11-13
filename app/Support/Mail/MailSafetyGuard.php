<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Providers\AppServiceProvider;
use Illuminate\Support\ConfigurationUrlParser;
use InvalidArgumentException;

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

    /** The verdicts of one transport. */
    private const SAFE = 'safe';

    private const REAL = 'sending';

    private const UNKNOWN = 'unknown';

    /**
     * The mailer that is SAFE to use for the given environment. Production keeps whatever is configured; every
     * other environment has a real-sending transport downgraded to `log` (Mailpit `smtp` / `log` / `array`
     * stay as-is — they never reach a real person).
     *
     * ⚠️⚠️ D-MAIL-GUARD-SHAPES (2026-11-11, his "do all"): with `$mailers` (the `mail.mailers` config — what the boot guard passes) the
     * mailer is judged by the TRANSPORT behind its name, the way Laravel builds it: a mailer with a `url` is whatever that URL says
     * (`MAIL_URL=postmark://…` under the mailer named `smtp` sends through Postmark — the host key still reads 127.0.0.1); a custom-named
     * mailer is its `transport` key; `failover` / `roundrobin` are every mailer in their `mailers` list. ⚠️ OUTSIDE production it FAILS
     * CLOSED: anything reachable that is real OR that it cannot read (an unknown transport, a cycle, an empty list, an unparseable URL) is
     * downgraded to `log`. Without `$mailers` (the older callers and tests) it judges by the name and the given host, exactly as before.
     *
     * @param  array<string, mixed>|null  $mailers
     */
    public static function safeMailerFor(string $environment, string $configured, ?string $smtpHost = null, ?array $mailers = null): string
    {
        if ($environment === 'production') {
            return $configured;
        }

        $verdicts = self::verdicts($configured, $smtpHost, $mailers);

        return $mailers === null
            ? (in_array(self::REAL, $verdicts, true) ? 'log' : $configured)               // the name-only rule, as it always was
            : (self::allSafe($verdicts) ? $configured : 'log');   // with config: only known-safe survives
    }

    /**
     * Whether a transport delivers to real inboxes — true when ANY transport reachable behind the mailer is a real one (used for
     * logging/telemetry and the boot decision; it never claims "sent").
     *
     * @param  array<string, mixed>|null  $mailers
     */
    public static function isSending(string $mailer, ?string $smtpHost = null, ?array $mailers = null): bool
    {
        return in_array(self::REAL, self::verdicts($mailer, $smtpHost, $mailers), true);
    }

    /**
     * Whether email from this app reaches real inboxes RIGHT NOW. ⚠️ The ONE question every screen asks before it says "sent by email"
     * (2026-10-05: the Guests page's Message said so while production's mailer was `log`, and recorded a sent message in each guest's
     * inbox; the New reservation page asked the same thing inline).
     *
     * ⚠️ D-MAIL-GUARD-SHAPES: read from the CONFIG the way Laravel builds the mailer, and CONSERVATIVE — true only when EVERY transport
     * reachable behind the mailer is a real one. A `failover` of a real SMTP and `log` is "maybe" and a claim of "sent" must not rest
     * on a maybe; an unknown transport is not a claim either. (A false here is the safe direction: the screen says nothing was sent.)
     */
    public static function sendingNow(): bool
    {
        $verdicts = self::verdicts((string) config('mail.default'), null, (array) config('mail.mailers'));

        return $verdicts !== [] && array_unique($verdicts) === [self::REAL];
    }

    /**
     * Every verdict reachable behind a mailer name.
     *
     * @param  array<string, mixed>|null  $mailers
     * @param  list<string>  $seen
     * @return list<string>
     */
    private static function verdicts(string $name, ?string $smtpHost, ?array $mailers, array $seen = []): array
    {
        if ($mailers === null) {
            // the NAME-only rule (no config to read): `smtp` by its host; the known real providers; anything else is not sending
            return [$name === 'smtp'
                ? (self::isLocalSmtpHost($smtpHost) ? self::SAFE : self::REAL)
                : (in_array($name, self::SENDING, true) ? self::REAL : self::SAFE)];
        }

        if (in_array($name, $seen, true)) {
            return [self::UNKNOWN];   // a cycle (a failover that lists itself)
        }
        $seen[] = $name;

        $config = $mailers[$name] ?? null;
        if (! is_array($config)) {
            // no such mailer: Laravel refuses it at send time; judged by the name so a downgrade still happens for a real provider
            return [in_array($name, self::SENDING, true) ? self::REAL : (in_array($name, ['log', 'array'], true) ? self::SAFE : self::UNKNOWN)];
        }

        $transport = (string) ($config['transport'] ?? $name);
        if (isset($config['url']) && is_string($config['url']) && $config['url'] !== '') {
            try {
                // exactly Laravel's own step (MailManager::getConfig): the URL's scheme becomes the transport, its host the host
                $config = array_merge($config, (new ConfigurationUrlParser)->parseConfiguration($config));
                $transport = (string) ($config['driver'] ?? $transport);
            } catch (InvalidArgumentException) {
                return [self::UNKNOWN];
            }
        }

        if ($transport === 'failover' || $transport === 'roundrobin' || $transport === 'rotating') {   // DEC-1: the rotating mailer is judged by its mailboxes
            $children = array_values(array_filter((array) ($config['mailers'] ?? []), 'is_string'));
            if ($children === []) {
                return [self::UNKNOWN];
            }

            return array_merge(...array_map(fn (string $child): array => self::verdicts($child, null, $mailers, $seen), $children));
        }

        return match (true) {
            $transport === 'smtp' => [self::isLocalSmtpHost(is_string($config['host'] ?? null) ? $config['host'] : null) ? self::SAFE : self::REAL],
            $transport === 'log' || $transport === 'array' => [self::SAFE],
            in_array($transport, self::SENDING, true) => [self::REAL],
            default => [self::UNKNOWN],
        };
    }

    /** @param  list<string>  $verdicts */
    private static function allSafe(array $verdicts): bool
    {
        return $verdicts !== [] && array_unique($verdicts) === [self::SAFE];
    }

    /** Whether an SMTP host is THIS machine (a local catch-inbox) rather than a real mail provider. */
    public static function isLocalSmtpHost(?string $host): bool
    {
        return in_array(strtolower(trim((string) $host)), self::LOCAL_SMTP_HOSTS, true);
    }
}
