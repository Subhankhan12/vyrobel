<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\MarketingContactMessage;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The internal notification that somebody used the public contact form (front-website Gate 7).
 *
 * ⚠️ IT GOES INWARD, NEVER OUTWARD. This mail is addressed to the operator of the business — the founder's
 * own inbox — and never to the visitor. ⚠️ NO AUTO-REPLY IS SENT: an automated "thanks, we'll be in touch"
 * would be a reply-time promise nothing keeps, and the audit found the design asserting "we reply within one
 * working day" twice with no support process behind it. The page tells the visitor their message was stored;
 * it does not tell them when a human will read it.
 *
 * ⚠️ THE MESSAGE IS ALREADY SAFE IN THE DATABASE BEFORE THIS IS QUEUED. If it never sends — and outside
 * `production` {@see MailSafetyGuard} guarantees it will not, downgrading every sending
 * transport to `log` — nothing is lost. That is the whole reason the row is written first.
 *
 * ⚠️ IT CARRIES A ULID, NOT THE BODY'S ONLY COPY. The email reproduces the message for convenience, but the
 * row id is included so the record can always be found even if the mail is truncated, filtered or lost.
 */
class MarketingContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly MarketingContactMessage $contact) {}

    public function envelope(): Envelope
    {
        // ⚠️ The topic is a validated enum value, never free text from the request — see
        // MarketingContactController::TOPICS. A subject line built from unvalidated input is a header-
        // injection surface and, more mundanely, a way to make internal mail unsearchable.
        $topic = $this->contact->topic !== null && $this->contact->topic !== ''
            ? ' · '.$this->contact->topic
            : '';

        return new Envelope(subject: 'Vyrobel contact form'.$topic);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing-contact',
            with: ['contact' => $this->contact],
        );
    }
}
