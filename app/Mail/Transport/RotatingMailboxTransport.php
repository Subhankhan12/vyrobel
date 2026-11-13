<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use App\Support\Mail\MailboxRotation;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * DEC-1 (his ruling, 2026-11-13) — SEND FROM TWO MAILBOXES. A mail transport that wraps one transport per mailbox and, for each
 * message, picks the FIRST mailbox that has not reached its daily limit ({@see MailboxRotation::usedInLastDay()}). Every pick
 * starts from the first mailbox, so it is used again the moment its own 24-hour window frees.
 *
 * ⚠️ THE MESSAGE IS SENT AS THE MAILBOX IT LEAVES FROM. Hostinger rejects mail sent as anyone but the mailbox that signs in
 * (the health board's "sender mismatch"), so the From ADDRESS is rewritten to the chosen mailbox's login and the envelope sender
 * with it. The display name, the Reply-To and the recipients are untouched — a property-branded guest email still reads as
 * "Seaside Hotel" and still replies to the hotel.
 *
 * ⚠️ A REFUSAL FALLS THROUGH. If a mailbox's server refuses the message (its own limit reached by a person sending by hand, a
 * login problem), the next mailbox is tried. If every mailbox is at its limit or refuses, this THROWS — the same loud failure a
 * single full mailbox gave before — and nothing is dropped silently.
 *
 * Counting is NOT done here: the MessageSent listener records each accepted message with the mailbox it left from (the envelope
 * sender), and {@see MailboxRotation} reads those rows back. One bookkeeping, one reader.
 */
final class RotatingMailboxTransport implements TransportInterface
{
    /**
     * @param  list<array{label: string, address: string, transport: TransportInterface, limit: int}>  $mailboxes  in order of preference
     */
    public function __construct(private array $mailboxes) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $lastError = null;
        $skippedFull = 0;

        foreach ($this->mailboxes as $mailbox) {
            if (MailboxRotation::usedInLastDay($mailbox['label']) >= $mailbox['limit']) {
                $skippedFull++;

                continue;
            }

            try {
                return $this->sendVia($mailbox, $message, $envelope);
            } catch (TransportExceptionInterface $e) {
                $lastError = $e;   // this mailbox's server refused — try the next one
            }
        }

        throw $lastError ?? new TransportException('Every mailbox has reached its daily limit ('.$skippedFull.' of '.count($this->mailboxes).') — nothing was sent. The limit frees on a rolling 24 hours.');
    }

    /**
     * @param  array{label: string, address: string, transport: TransportInterface, limit: int}  $mailbox
     */
    private function sendVia(array $mailbox, RawMessage $message, ?Envelope $envelope): ?SentMessage
    {
        if (! $message instanceof Email) {
            return $mailbox['transport']->send($message, $envelope);
        }

        $email = clone $message;
        $from = $email->getFrom()[0] ?? null;
        if ($from === null || strcasecmp($from->getAddress(), $mailbox['address']) !== 0) {
            $email->from(new Address($mailbox['address'], $from?->getName() ?? ''));
        }

        // the envelope sender is the mailbox that signs in; the recipients are exactly what they were
        $recipients = $envelope !== null ? $envelope->getRecipients() : Envelope::create($email)->getRecipients();

        return $mailbox['transport']->send($email, new Envelope(new Address($mailbox['address']), $recipients));
    }

    public function __toString(): string
    {
        return 'rotating://'.count($this->mailboxes);
    }
}
