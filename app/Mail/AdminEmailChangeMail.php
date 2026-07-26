<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * SUPER ADMIN · ACCOUNT — "confirm your new email", sent TO THE NEW ADDRESS when a platform admin requests a
 * login-email change. Opening the signed link is what switches the email (proving control of the new inbox);
 * the address stays unchanged until then. Best-effort — a send failure never rolls back the request (the
 * pending email is already parked; the admin can re-request). Mirrors {@see OwnerEmailChangeMail}.
 */
class AdminEmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $adminName,
        public readonly string $newEmail,
        public readonly string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your new Vyrobel platform email');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-email-change',
            with: [
                'adminName' => $this->adminName,
                'newEmail' => $this->newEmail,
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
