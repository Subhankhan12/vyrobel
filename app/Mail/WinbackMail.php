<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: re-engagement / winback (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER (a scheduled sweep of guests
 * who haven't stayed in a while) is DEFERRED — the same scheduled-command shape as payment-reminders. Copy is
 * deliberately generic: NO fabricated discount or offer (there is no promo engine), just a warm invitation back.
 */
class WinbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $guestName, public readonly ?string $propertyName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We’d love to welcome you back');
    }

    public function content(): Content
    {
        // The optional "at <property>" phrase is built here (not with an inline @if) so the flowing copy never
        // writes `word@directive` — the Blade \B trap that ships raw source (invariant / CLAUDE.md).
        $place = ($this->propertyName !== null && $this->propertyName !== '') ? ' at '.$this->propertyName : '';

        return new Content(
            view: 'emails.winback',
            with: [
                'guestName' => $this->guestName,
                'place' => $place,
            ],
        );
    }
}
