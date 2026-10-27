<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The password-reset email (Auth Phase B · finishing sweep). Carries the tokened, single-use, expiring
 * reset link (the token is owned + validated by Laravel's Password broker). Email-safe HTML, brand-
 * consistent with the invite email (table layout, inline CSS, terracotta bulletproof button). Lands in
 * Mailpit in dev; production needs a real transactional provider.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $email,
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('php/mail/password_reset_mail.subject'));
    }

    public function content(): Content
    {
        // The broker's reset route takes the token in the path + the email as a query param — the same
        // pair `Password::reset()` re-validates on submit.
        $resetUrl = route('password.reset', ['token' => $this->token]).'?email='.urlencode($this->email);

        return new Content(
            view: 'emails.password-reset',
            with: [
                'userName' => $this->userName !== '' ? $this->userName : __('php/mail/password_reset_mail.there'),
                'resetUrl' => $resetUrl,
                'expiresMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 60),
            ],
        );
    }
}
