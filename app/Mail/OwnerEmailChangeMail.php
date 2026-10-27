<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Owner "confirm your new email" — sent TO THE NEW ADDRESS when an owner-realm user requests a login-email
 * change. Opening the signed link is what actually switches the email (proving control of the new inbox);
 * the address stays unchanged until then. Best-effort, like every transactional mail — a send failure never
 * rolls back the request (the pending email is already parked; the user can re-request).
 */
class OwnerEmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $newEmail,
        public readonly string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('php/mail/owner_email_change_mail.subject'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner-email-change',
            with: [
                'userName' => $this->userName,
                'newEmail' => $this->newEmail,
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
