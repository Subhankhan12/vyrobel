<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\Mail\MailSafetyGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Email to me" (owner completeness sweep · Wave A · GATE 4) — the owner's latest finalized STATEMENT PDF,
 * delivered to their own inbox. QUEUED (a mail failure never blocks the request), and the same
 * {@see MailSafetyGuard} that protects every send applies (no stray real send off a demo/dev
 * env). The PDF bytes are built in the controller — where the OwnerContext/OwnerScope is active — and passed in,
 * so the queue worker never has to re-run an owner-scoped query with no context.
 *
 * SELF-ONLY: the controller sends ONLY to the authenticated owner's own address; there is no recipient in the
 * request. The attachment is the owner's OWN statement (owner-scoped, CAP_EXPORTS-gated at the route). No guest
 * data is present (a statement carries none, #28).
 */
class OwnerReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ownerName,
        public readonly string $periodLabel,
        public readonly string $filename,
        public readonly string $pdfBytes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Vyrobel statement — '.$this->periodLabel);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner-report',
            with: [
                'ownerName' => $this->ownerName,
                'periodLabel' => $this->periodLabel,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfBytes, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
