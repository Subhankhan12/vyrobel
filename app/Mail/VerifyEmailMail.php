<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\GuestAccountService;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Guest: verify your email (transactional-emails exact-match) — the real trigger is the booking-tied CLAIM flow
 * ({@see GuestAccountService::sendClaimLink()}), which sends a signed, expiring `guest.claim.verify`
 * link. This is NOT open signup: an account can only be claimed for an email that already has a real booking, and
 * nothing activates until the link is followed.
 *
 * HONESTY: `$expiresLabel` reflects the REAL signed-link lifetime (1 hour) — never the wireframe's 24h. There is
 * NO welcome-credit feature (the guest wallet has no signup grant), so the "€X credit is waiting" teaser is
 * OMITTED entirely — never a fabricated credit (#39).
 *
 * `$organizationId` (the claiming guest's org) lets this email honour the operator's per-org customization
 * ({@see EmailTemplateResolver} #86); null → the code default.
 */
class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $guestName,
        public readonly string $verifyUrl,
        public readonly ?string $expiresLabel = null,
        public readonly ?string $email = null,
        public readonly ?string $organizationId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template()->subject());
    }

    public function content(): Content
    {
        $tpl = $this->template();

        return new Content(
            view: 'emails.verify-email',
            with: [
                'guestName' => $this->guestName,
                'verifyUrl' => $this->verifyUrl,
                'expiresLabel' => $this->expiresLabel ?? __('php/mail/verify_email_mail.one_hour'),
                'email' => $this->email,
                // Operator-customizable template (#86)
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => ['copy_link' => $tpl->block('copy_link')],
            ],
        );
    }

    private function template(): ResolvedEmailTemplate
    {
        $first = trim(Str::before($this->guestName, ' '));

        return EmailTemplateResolver::for($this->organizationId, 'verify-email', [
            'first_name' => $first !== '' ? $first : $this->guestName,
        ]);
    }
}
