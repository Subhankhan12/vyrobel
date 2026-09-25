<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Support\Mail\MailSendLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * ⚠️ MAIL 02 — records that the mail server ACCEPTED a message (the SMTP conversation ended in success), so the health
 * board can show real "sent" counts. ⚠️ "Accepted" is not "delivered": with SMTP there is no provider event feed, and a
 * bounce comes back later as an email to the sending mailbox — the board says exactly that.
 */
final class RecordAcceptedMail
{
    public function handle(MessageSent $event): void
    {
        MailSendLog::accepted(
            (string) ($event->data['__laravel_mailable'] ?? 'unknown'),
            $event->sent->getMessageId(),
        );
    }
}
