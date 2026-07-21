<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use App\Models\OwnerTeamInvite;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The owner-TEAM invite email (Phase 7c · SG3) — carries the SINGLE-USE tokened set-password link to the
 * OWNER-REALM claim (`/team-invite/{token}`). Reuses the shared `emails.owner-invite` view with team copy:
 * the invitee is told they've been added to an owner's team at a specific role. Token plaintext is passed
 * transiently (never persisted).
 */
class OwnerTeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OwnerTeamInvite $invite,
        public readonly string $token,
        public readonly string $inviteeName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You’re invited to a Vyrobel owner team');
    }

    public function content(): Content
    {
        $orgName = (string) (Organization::query()->whereKey($this->invite->organization_id)->value('name') ?? 'Vyrobel');
        $roleLabel = OwnerTeamRole::label((string) $this->invite->role);

        return new Content(
            view: 'emails.owner-invite',
            with: [
                'ownerName' => $this->inviteeName,
                'orgName' => $orgName,
                'acceptUrl' => url('/team-invite/'.$this->token),
                'expiresAt' => $this->invite->expires_at,
                'accountLabel' => 'owner-team account',
                'headline' => "Your {$roleLabel} access on {$orgName}",
                'intro' => "You’ve been invited to join an owner team on {$orgName} in the Vyrobel owner portal as {$roleLabel}. Set your password to activate your account and get started.",
            ],
        );
    }
}
