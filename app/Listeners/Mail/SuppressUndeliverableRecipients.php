<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Support\Mail\MailSendLog;
use App\Support\Mail\UndeliverableRecipients;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/**
 * ⚠️⚠️ MAIL 02 — runs before EVERY email leaves the application, queued or inline, whoever sends it (one listener on
 * the framework's MessageSending event, so no send site can forget it). Recipients that can never receive mail
 * ({@see UndeliverableRecipients}) are removed; if no To recipient is left, the send is CANCELLED — returning false is
 * the framework's documented way to stop a message.
 *
 * ⚠️ Logs the REASON and the email TYPE, never the address — a guest's address is encrypted personal data (#28) and a
 * log line would copy it out in plaintext.
 */
final class SuppressUndeliverableRecipients
{
    public function handle(MessageSending $event): bool
    {
        $message = $event->message;
        $reasons = [];
        $keep = static function (array $addresses) use (&$reasons): array {
            return array_values(array_filter($addresses, static function (Address $a) use (&$reasons): bool {
                $why = UndeliverableRecipients::reason($a->getAddress());
                if ($why !== null) {
                    $reasons[] = $why;

                    return false;
                }

                return true;
            }));
        };

        $to = $keep($message->getTo());
        $cc = $keep($message->getCc());
        $bcc = $keep($message->getBcc());

        if ($reasons === []) {
            return true;
        }

        $mailable = (string) ($event->data['__laravel_mailable'] ?? 'unknown');
        MailSendLog::suppressed($mailable, count($reasons));
        Log::info('Mail to an undeliverable address was suppressed before sending.', [
            'mailable' => $mailable,
            'suppressed' => count($reasons),
            'reasons' => array_values(array_unique($reasons)),
            'sent_to_others' => $to !== [],
        ]);

        if ($to === []) {
            return false;   // nobody real left to send it to — cancel the whole message
        }

        $message->to(...$to);
        $message->cc(...$cc);
        $message->bcc(...$bcc);

        return true;
    }
}
