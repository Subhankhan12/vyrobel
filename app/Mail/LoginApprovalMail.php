<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\Mail\MailSafetyGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The LOGIN-APPROVAL email (Wave B · GATE 4) — sent when a sign-in is held pending on a new device. Carries the
 * single-use, expiring approval link (a signed URL). QUEUED; the same {@see MailSafetyGuard}
 * that guards every send applies. The device/IP/time are shown so the user can spot an approval they didn't start.
 */
class LoginApprovalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $approveUrl,
        public readonly string $device,
        public readonly string $ip,
        public readonly string $when,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('php/mail/login_approval_mail.subject'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-approval',
            with: [
                'userName' => $this->userName,
                'approveUrl' => $this->approveUrl,
                'device' => $this->device,
                'ip' => $this->ip,
                'when' => $this->when,
            ],
        );
    }
}
