<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Mail\Transport\RotatingMailboxTransport;
use Illuminate\Support\Facades\DB;

/**
 * DEC-1 (his ruling, 2026-11-13) — THE ROTATION POLICY: which mailboxes the app may send from, and how many each has used.
 *
 * Hostinger caps how many emails one mailbox may send in a rolling 24 hours. The app can send from two mailboxes (contact@
 * and sales@): the FIRST in `mail.mailers.rotating.mailers` is used until it reaches the cap, then the next; and because every
 * choice starts again from the first, the first is back in use the moment its own window frees — "shuffle back" needs no timer.
 *
 * ⚠️ THE LIMIT IS HOSTINGER'S, NOT OURS: `MAIL_DAILY_LIMIT` (default 100, Reported — hPanel is the authority, and Hostinger may
 * change it). It is counted per mailbox from `mail_send_log` (accepted, last 24 hours, by the `mailbox` label). It does not see
 * a person sending by hand from the same mailbox — when the host refuses for that reason the transport falls through to the next
 * mailbox on the refusal itself ({@see RotatingMailboxTransport}).
 *
 * ⚠️ NO SECRET AND NO ADDRESS leaves this class: a mailbox is a mailer NAME (the label stored in the log); the login is read from
 * config by the transport and never copied anywhere.
 */
final class MailboxRotation
{
    public const MAILER = 'rotating';

    /** Hostinger's daily cap per mailbox when `MAIL_DAILY_LIMIT` is not set (Reported, not measured). */
    public const DEFAULT_DAILY_LIMIT = 100;

    /**
     * The mailboxes the rotation may use, in order of preference — only those with a login. A child mailer without a username
     * (or, for SMTP, without a password) is skipped: an unconfigured second mailbox is simply not there.
     *
     * @return list<array{label: string, address: string}>
     */
    public static function mailboxes(): array
    {
        $out = [];
        foreach ((array) config('mail.mailers.'.self::MAILER.'.mailers', []) as $name) {
            $config = (array) config("mail.mailers.{$name}", []);
            $address = trim((string) ($config['username'] ?? ''));
            if ($address === '') {
                continue;
            }
            if (($config['transport'] ?? null) === 'smtp' && blank($config['password'] ?? null)) {
                continue;
            }
            $out[] = ['label' => (string) $name, 'address' => $address];
        }

        return $out;
    }

    /** One mailbox's daily limit. */
    public static function dailyLimit(): int
    {
        $limit = (int) config('mail.mailers.'.self::MAILER.'.daily_limit', self::DEFAULT_DAILY_LIMIT);

        return $limit > 0 ? $limit : self::DEFAULT_DAILY_LIMIT;
    }

    /** How many messages this mailbox's server accepted in the last 24 hours — read from the send log, by label. */
    public static function usedInLastDay(string $label): int
    {
        return DB::table('mail_send_log')
            ->where('mailbox', $label)
            ->where('outcome', MailSendLog::ACCEPTED)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /** The label of the configured mailbox whose login is this address (case-insensitive), or null. */
    public static function labelFor(?string $address): ?string
    {
        $address = strtolower(trim((string) $address));
        if ($address === '') {
            return null;
        }
        foreach (self::mailboxes() as $mailbox) {
            if (strtolower($mailbox['address']) === $address) {
                return $mailbox['label'];
            }
        }

        return null;
    }
}
