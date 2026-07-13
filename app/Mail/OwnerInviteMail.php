<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use App\Models\OwnerInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The invite email (Auth Phase B). Carries the SINGLE-USE tokened set-password link. The token plaintext
 * is passed in transiently (never persisted on the model). The copy is ROLE-AWARE — an owner is told
 * about their portal; a staff invitee (Hotel Manager / Accountant) is told about the operator app —
 * so the email never mislabels a manager as an "owner". Email-safe HTML (Step 3).
 */
class OwnerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OwnerInvite $invite,
        public readonly string $token,
        public readonly string $ownerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You’re invited to Vyrobel');
    }

    public function content(): Content
    {
        // Organizations aren't tenant-scoped, so resolve by key (a typed value() query, not the
        // generically-typed BelongsToOrganization relation).
        $orgName = (string) (Organization::query()->whereKey($this->invite->organization_id)->value('name') ?? 'Vyrobel');
        $copy = $this->copyFor((string) $this->invite->role, $orgName);

        return new Content(
            view: 'emails.owner-invite',
            with: [
                'ownerName' => $this->ownerName,
                'orgName' => $orgName,
                'acceptUrl' => url('/invite/'.$this->token),
                'expiresAt' => $this->invite->expires_at,
                'accountLabel' => $copy['account'],
                'headline' => $copy['headline'],
                'intro' => $copy['intro'],
            ],
        );
    }

    /**
     * Role-appropriate email copy. Owner → the owner portal; Hotel Manager / Accountant → the operator
     * app (with the scope each role actually gets). Unknown roles fall back to the owner copy.
     *
     * @return array{account: string, headline: string, intro: string}
     */
    private function copyFor(string $role, string $orgName): array
    {
        return match ($role) {
            'manager' => [
                'account' => 'Hotel Manager account',
                'headline' => "Your Hotel Manager access on {$orgName}",
                'intro' => "You’ve been invited to join {$orgName} on Vyrobel as Hotel Manager — the operator app for day-to-day operations (calendar, front desk, housekeeping, reservations and folios). Set your password to activate your account and get started.",
            ],
            'accountant' => [
                'account' => 'Accountant account',
                'headline' => "Your Accountant access on {$orgName}",
                'intro' => "You’ve been invited to join {$orgName} on Vyrobel as Accountant — access to folios, payments, invoices, taxes and reports. Set your password to activate your account and get started.",
            ],
            default => [
                'account' => 'owner account',
                'headline' => "Your owner portal on {$orgName}",
                'intro' => 'You’ve been invited to the Vyrobel owner portal, where you can see your units, earnings, statements and payouts. Set your password to activate your account and get started.',
            ],
        };
    }
}
