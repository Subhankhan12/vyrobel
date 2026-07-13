<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OwnerInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The owner-invite email (Auth Phase B). Carries the SINGLE-USE tokened set-password link. The token
 * plaintext is passed in transiently (never persisted on the model). Styled email-safe HTML in Step 3.
 */
class OwnerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OwnerInvite $invite,
        public readonly string $token,
        public readonly string $ownerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You’re invited to Vyrobel');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner-invite',
            with: [
                'ownerName' => $this->ownerName,
                'acceptUrl' => url('/invite/'.$this->token),
                'expiresAt' => $this->invite->expires_at,
            ],
        );
    }
}
