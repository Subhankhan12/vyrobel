<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: verify your email (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER is DEFERRED to Phase 4 (guest
 * accounts / the guest portal) — there is no guest-auth flow to issue a real verification link yet, so this takes
 * the URL as a parameter and is shown via the Email Previews. Phase 4 wires it into guest sign-up.
 */
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $guestName, public readonly string $verifyUrl) {}

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
            ],
        );
    }
}
