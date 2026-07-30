<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\GuestAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: verify your email (transactional-emails exact-match) — the real trigger is the booking-tied CLAIM flow
 * ({@see GuestAccountService::sendClaimLink()}), which sends a signed, expiring `guest.claim.verify`
 * link. This is NOT open signup: an account can only be claimed for an email that already has a real booking, and
 * nothing activates until the link is followed.
 *
 * HONESTY: `$expiresLabel` reflects the REAL signed-link lifetime (1 hour) — never the wireframe's 24h. There is
 * NO welcome-credit feature (the guest wallet has no signup grant), so the "€X credit is waiting" teaser is
 * OMITTED entirely — never a fabricated credit (#39).
 */
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $verifyUrl,
        public readonly string $expiresLabel = '1 hour',
        public readonly ?string $email = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your email address');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'guestName' => $this->guestName,
                'verifyUrl' => $this->verifyUrl,
                'expiresLabel' => $this->expiresLabel,
                'email' => $this->email,
            ],
        );
    }
}
