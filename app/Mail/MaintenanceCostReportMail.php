<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The maintenance costs report emailed to the operator's OWN inbox (#89) — a real send (MailSafetyGuard →
 * log/Mailpit outside production) carrying the real PDF as an attachment. Never sent to a fabricated
 * accountant address.
 */
class MaintenanceCostReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $pdfBytes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your maintenance costs report');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.maintenance-cost-report');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfBytes, 'maintenance-costs.pdf')->withMime('application/pdf'),
        ];
    }
}
