<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: a waitlisted date has opened up (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER is DEFERRED to
 * Phase 4 (the waitlist feature) — there is no waitlist model or event yet, so this takes plain scalars and is
 * shown via the Email Previews. When Phase 4 ships the waitlist, it constructs + sends this on a real opening.
 */
class WaitlistNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $propertyName,
        public readonly string $dates,
        public readonly ?string $bookUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('php/mail/waitlist_notification_mail.subject', ['property' => $this->propertyName]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist-notification',
            with: [
                'guestName' => $this->guestName,
                'propertyName' => $this->propertyName,
                'dates' => $this->dates,
                'bookUrl' => $this->bookUrl,
            ],
        );
    }
}
