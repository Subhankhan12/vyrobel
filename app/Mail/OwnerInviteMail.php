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
                'operator' => $orgName,
                'acceptUrl' => url('/invite/'.$this->token),
                'expiresAt' => $this->invite->expires_at,
                'accountLabel' => $copy['account'],
                'eyebrow' => $copy['eyebrow'],
                'headline' => $copy['headline'],
                'intro' => $copy['intro'],
                'sectionTitle' => $copy['section_title'],
                'features' => $copy['features'],
                'roleReason' => $copy['role_reason'],
            ],
        );
    }

    /**
     * Role-appropriate email copy — matched to "Owner Invite Email.html". Owner → the owner portal ("In your
     * portal"); Accountant → the financial read/export scope ("What you can access"); Manager (Hotel Manager) →
     * the operator app's operations. Unknown roles fall back to the owner copy. The feature lists are honest,
     * static descriptions of what each role/portal actually gives — no per-account data.
     *
     * @return array{account: string, eyebrow: string, headline: string, intro: string, section_title: string, features: list<array{title: string, desc: string}>, role_reason: string}
     */
    private function copyFor(string $role, string $orgName): array
    {
        return match ($role) {
            'manager' => [
                'account' => 'Hotel Manager account',
                'eyebrow' => 'Property manager invitation',
                'headline' => "Your Hotel Manager access on {$orgName}",
                'intro' => "{$orgName} has invited you to join their team on Vyrobel as a property manager — the operator app for day-to-day operations. Set your password to get started.",
                'section_title' => 'In the operator app',
                'features' => [
                    ['title' => 'Calendar & front desk', 'desc' => 'Arrivals, departures and the booking calendar'],
                    ['title' => 'Housekeeping & maintenance', 'desc' => 'Turnovers, tasks and work orders'],
                    ['title' => 'Reservations & guests', 'desc' => 'Bookings, folios and guest messaging'],
                ],
                'role_reason' => 'invited you as a property manager.',
            ],
            'accountant' => [
                'account' => 'Accountant account',
                'eyebrow' => 'Accountant access invitation',
                'headline' => "Your Accountant access on {$orgName}",
                'intro' => "{$orgName} has given you accountant access to their properties on Vyrobel — statements, tax documents and payout records, all ready to export. Set your password to get started.",
                'section_title' => 'What you can access',
                'features' => [
                    ['title' => 'Statements & ledgers', 'desc' => 'Every booking, fee and payout — itemised and exportable'],
                    ['title' => 'Tax documents', 'desc' => 'VAT invoices, annual summaries and withholding'],
                    ['title' => 'Payout reconciliation', 'desc' => 'Match payouts to statements across every unit'],
                ],
                'role_reason' => 'invited you as an accountant.',
            ],
            default => [
                'account' => 'owner account',
                'eyebrow' => 'Owner portal invitation',
                'headline' => "Your owner portal on {$orgName}",
                'intro' => "{$orgName} has invited you to your owner portal, where you can see your statements, payouts and unit performance — all in one place. Set your password to get started.",
                'section_title' => 'In your portal',
                'features' => [
                    ['title' => 'Monthly statements', 'desc' => 'Every booking, fee and payout, itemised'],
                    ['title' => 'Payouts & schedule', 'desc' => 'Track what’s on the way and when it lands'],
                    ['title' => 'Unit performance', 'desc' => 'Occupancy, rates and reviews per unit'],
                ],
                'role_reason' => 'invited you as a property owner.',
            ],
        };
    }
}
