<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The GUEST realm's password-reset email (Phase 4a · Sub-gate 3) — the guest equivalent of {@see PasswordResetMail}
 * on the shared brand layout. The link carries ONLY the token (no email in the URL); the guest re-enters their
 * email on the reset form, which the broker resolves via the blind index. Mailpit in dev; prod needs a provider.
 */
class GuestPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $token, public readonly string $guestName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Vyrobel password');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-password-reset',
            with: [
                'guestName' => $this->guestName,
                'resetUrl' => URL::route('guest.password.reset', ['token' => $this->token]),
            ],
        );
    }
}
