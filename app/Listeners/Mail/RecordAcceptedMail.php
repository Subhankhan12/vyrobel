<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Support\Mail\MailboxRotation;
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
        // DEC-1 — which mailbox it left from, read from the ENVELOPE sender (the mailbox that signed in): the rotation counts from this
        $sender = $event->sent->getEnvelope()->getSender()->getAddress();

        MailSendLog::accepted(MailSendLog::typeOf($event->data), $event->sent->getMessageId(), MailboxRotation::labelFor($sender));
    }
}
