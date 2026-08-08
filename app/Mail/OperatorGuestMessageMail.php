<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\Guest\MarketingUnsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Operator → guest free-text message (#87 — the Guests-list bulk "Message"). A real email carrying exactly the
 * operator's typed body, delivered to the guest's own address (MailSafetyGuard → log/Mailpit outside
 * production). It is recorded as a manual inbox thread too, so the operator keeps a record and a future inbound
 * reply (account-gated) attaches to it — "replies land in your inbox."
 *
 * The body is the operator's own words (no fabricated content, #39). `$propertyName` is the sender identity.
 *
 * ⚠️ MARKETING vs SERVICE. The same Mailable carries both: the Guests-list bulk action marks a send as marketing
 * or not. A MARKETING send passes the unsubscribe URLs, so the footer link and the RFC 2369/8058
 * `List-Unsubscribe` headers appear; an operational reply passes neither and stays a plain service message.
 */
class OperatorGuestMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $bodyText,
        public readonly ?string $propertyName = null,
        public readonly ?string $unsubscribeUrl = null,
        public readonly ?string $unsubscribeOneClickUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = ($this->propertyName !== null && $this->propertyName !== '') ? $this->propertyName : 'Your host';

        return new Envelope(subject: 'A message from '.$from);
    }

    /** RFC 2369/8058 unsubscribe headers — present ONLY on a marketing send (empty otherwise). */
    public function headers(): Headers
    {
        return MarketingUnsubscribe::mailHeaders($this->unsubscribeOneClickUrl);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.operator-guest-message',
            with: [
                'guestName' => $this->guestName,
                'bodyText' => $this->bodyText,
                'propertyName' => $this->propertyName,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
