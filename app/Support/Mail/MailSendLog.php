<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * ⚠️ MAIL 02 — the ONE writer and reader of `mail_send_log`: which email TYPE the mail server accepted, or the app
 * suppressed, and when. ⚠️ NO ADDRESS, NO SUBJECT, NO BODY — nothing personal is stored (the message id is the
 * server's own opaque reference). Pruned after 365 days (RetentionPolicy — the founder's ruling, 2026-09-25).
 *
 * ⚠️ Recording must never break sending: a failure to write a log row is logged and swallowed — the email itself is
 * what matters.
 */
final class MailSendLog
{
    public const ACCEPTED = 'accepted';

    public const SUPPRESSED = 'suppressed';

    public static function accepted(string $mailable, ?string $messageId): void
    {
        self::write(self::ACCEPTED, $mailable, $messageId, 0);
    }

    public static function suppressed(string $mailable, int $recipients): void
    {
        self::write(self::SUPPRESSED, $mailable, null, $recipients);
    }

    /**
     * WHICH email this is, from the data Laravel hands the mail events. ⚠️ A Mailable carries its class under
     * `__laravel_mailable`; a NOTIFICATION sent by email (the verification link, a reset notice) carries it under
     * `__laravel_notification` instead. 🔬 Reading only the first recorded every notification as "unknown" — found
     * on production (TONIGHT 01), on the first real sign-up's verification link.
     *
     * @param  array<string, mixed>  $data
     */
    public static function typeOf(array $data): string
    {
        return (string) ($data['__laravel_mailable'] ?? $data['__laravel_notification'] ?? 'unknown');
    }

    /** How many messages had this outcome since the given moment. */
    public static function count(string $outcome, \DateTimeInterface $since): int
    {
        return DB::table('mail_send_log')->where('outcome', $outcome)->where('created_at', '>=', $since)->count();
    }

    private static function write(string $outcome, string $mailable, ?string $messageId, int $suppressed): void
    {
        try {
            DB::table('mail_send_log')->insert([
                'id' => (string) Str::ulid(),
                'outcome' => $outcome,
                'mailable' => Str::limit(class_basename($mailable), 120, ''),
                'message_id' => $messageId !== null ? Str::limit($messageId, 250, '') : null,
                'suppressed_recipients' => $suppressed,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not record a mail send outcome.', ['outcome' => $outcome, 'error' => $e::class]);
        }
    }
}
