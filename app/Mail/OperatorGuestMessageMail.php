<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Property;
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
 * reply (account-gated) attaches to it.
 *
 * ⚠️⚠️ 2026-10-05 — SENT AS THE PROPERTY ({@see BrandsGuestMail}): its name as the sender and its REPLY-TO address, like every
 * other guest email. Before, it carried neither, so a guest's reply went to Vyrobel's own sender address while the email said
 * "Just reply to this email and we'll see it" and the Guests page said "replies land in your inbox" (no email reply ever
 * does — there is no inbound mail). The invitation to reply is now printed only when a reply-to address exists.
 *
 * The body is the operator's own words (no fabricated content, #39). `$propertyName` is the sender identity.
 *
 * ⚠️ MARKETING vs SERVICE. The same Mailable carries both: the Guests-list bulk action marks a send as marketing
 * or not. A MARKETING send passes the unsubscribe URLs, so the footer link and the RFC 2369/8058
 * `List-Unsubscribe` headers appear; an operational reply passes neither and stays a plain service message.
 */
class OperatorGuestMessageMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $bodyText,
        public readonly ?string $propertyName = null,
        public readonly ?string $unsubscribeUrl = null,
        public readonly ?string $unsubscribeOneClickUrl = null,
        public readonly ?Property $property = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = ($this->propertyName !== null && $this->propertyName !== '') ? $this->propertyName : __('php/mail/operator_guest_message_mail.your_host');

        return $this->brandedEnvelope(__('php/mail/operator_guest_message_mail.subject', ['sender' => $from]), $this->brandFor($this->property));
    }

    /** RFC 2369/8058 unsubscribe headers — present ONLY on a marketing send (empty otherwise). */
    public function headers(): Headers
    {
        return MarketingUnsubscribe::mailHeaders($this->unsubscribeOneClickUrl);
    }

    public function content(): Content
    {
        $brand = $this->brandFor($this->property);

        return new Content(
            view: 'emails.operator-guest-message',
            with: [
                'brand' => $brand,
                'replyTo' => $brand->replyTo,   // the reply line is printed only when a reply reaches the property
                'guestName' => $this->guestName,
                'bodyText' => $this->bodyText,
                'propertyName' => $this->propertyName,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
