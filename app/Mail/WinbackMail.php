<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\Guest\MarketingUnsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Guest: re-engagement / winback (Phase 3 · Sub-gate 4). TEMPLATE BUILT; the TRIGGER (a scheduled sweep of guests
 * who haven't stayed in a while) is DEFERRED — the same scheduled-command shape as payment-reminders. Copy is
 * deliberately generic: NO fabricated discount or offer (there is no promo engine), just a warm invitation back.
 *
 * ⚠️ MARKETING. This is not a service message, so it carries a working unsubscribe — the footer link plus the
 * RFC 2369/8058 `List-Unsubscribe` headers ({@see MarketingUnsubscribe}). Any future sender MUST pass
 * `$unsubscribeUrl`; a marketing send without one is the defect this closed.
 */
class WinbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly ?string $propertyName = null,
        public readonly ?string $unsubscribeUrl = null,
        public readonly ?string $unsubscribeOneClickUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('php/mail/winback_mail.subject'));
    }

    /** RFC 2369/8058 unsubscribe headers — present ONLY on a marketing send (empty otherwise). */
    public function headers(): Headers
    {
        return MarketingUnsubscribe::mailHeaders($this->unsubscribeOneClickUrl);
    }

    public function content(): Content
    {
        // The optional "at <property>" phrase is built here (not with an inline @if) so the flowing copy never
        // writes `word@directive` — the Blade \B trap that ships raw source (invariant / CLAUDE.md).
        $place = ($this->propertyName !== null && $this->propertyName !== '') ? ' '.__('php/mail/winback_mail.at_property', ['property' => $this->propertyName]) : '';

        return new Content(
            view: 'emails.winback',
            with: [
                'guestName' => $this->guestName,
                'place' => $place,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
