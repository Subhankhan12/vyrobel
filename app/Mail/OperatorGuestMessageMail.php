<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Operator → guest free-text message (#87 — the Guests-list bulk "Message"). A real email carrying exactly the
 * operator's typed body, delivered to the guest's own address (MailSafetyGuard → log/Mailpit outside
 * production). It is recorded as a manual inbox thread too, so the operator keeps a record and a future inbound
 * reply (account-gated) attaches to it — "replies land in your inbox."
 *
 * The body is the operator's own words (no fabricated content, #39). `$propertyName` is the sender identity.
 */
class OperatorGuestMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $bodyText,
        public readonly ?string $propertyName = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = ($this->propertyName !== null && $this->propertyName !== '') ? $this->propertyName : 'Your host';

        return new Envelope(subject: 'A message from '.$from);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.operator-guest-message',
            with: [
                'guestName' => $this->guestName,
                'bodyText' => $this->bodyText,
                'propertyName' => $this->propertyName,
            ],
        );
    }
}
