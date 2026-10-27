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
        return new Envelope(subject: __('php/mail/owner_team_invite_mail.subject'));
    }

    public function content(): Content
    {
        $orgName = (string) (Organization::query()->whereKey($this->invite->organization_id)->value('name') ?? 'Vyrobel');
        $role = (string) $this->invite->role;
        $roleLabel = OwnerTeamRole::label($role);

        return new Content(
            view: 'emails.owner-invite',
            with: [
                'ownerName' => $this->inviteeName,
                'orgName' => $orgName,
                'operator' => $orgName,
                'acceptUrl' => url('/team-invite/'.$this->token),
                'expiresAt' => $this->invite->expires_at,
                'accountLabel' => __('php/mail/owner_team_invite_mail.account_label'),
                'eyebrow' => __('php/mail/owner_team_invite_mail.eyebrow', ['role' => $roleLabel]),
                'headline' => __('php/mail/owner_team_invite_mail.headline', ['role' => $roleLabel, 'org' => $orgName]),
                'intro' => __('php/mail/owner_team_invite_mail.intro', ['org' => $orgName, 'role' => $roleLabel]),
                'sectionTitle' => __('php/mail/owner_team_invite_mail.section_title'),
                'features' => self::featuresFor($role),
                'roleReason' => __('php/mail/owner_team_invite_mail.role_reason', ['role' => $roleLabel]),
            ],
        );
    }

    /**
     * ⚠️⚠️ THE FIVE VARIABLES THIS MAILER USED TO OMIT — and why the fix is HERE, not in the template.
     *
     * `emails/owner-invite` is shared with {@see OwnerInviteMail}, which passes all twelve. This one passed
     * seven, so `{{ $eyebrow }}` (the first of five, at line 70) threw `Undefined variable` and **the send
     * failed for every owner-team invite ever attempted** — after the invite row and the invitee's User row
     * were already committed.
     *
     * ⚠️ The template was NOT made tolerant instead, and that is the deliberate choice: the badge, the feature
     * list and the closing reason are the email's CONTENT, not decoration. Defaulting them to empty would have
     * turned a hard failure into a half-blank invitation that still looks sent — a worse outcome, and the
     * inconsistency was never the template's: it is that two mailers disagreed about a shared contract.
     *
     * ⚠️ THE FEATURE COPY IS DERIVED FROM THE REAL CAPABILITY MATRIX ({@see OwnerTeamRole::CAPABILITIES}), not
     * written to sound good. A co-owner really does hold approvals and payout; an accountant really has no
     * calendar and no approvals; view-only really has neither documents nor exports. Promising an invitee
     * something their role does not grant would be a fabricated claim in a transactional email.
     *
     * @return list<array{title: string, desc: string}>
     */
    private static function featuresFor(string $role): array
    {
        return match ($role) {
            OwnerTeamRole::CO_OWNER => [
                ['title' => __('php/mail/owner_team_invite_mail.earnings_title'), 'desc' => __('php/mail/owner_team_invite_mail.earnings_desc')],
                ['title' => __('php/mail/owner_team_invite_mail.calendar_title'), 'desc' => __('php/mail/owner_team_invite_mail.calendar_desc')],
                ['title' => __('php/mail/owner_team_invite_mail.approvals_title'), 'desc' => __('php/mail/owner_team_invite_mail.approvals_desc')],
            ],
            OwnerTeamRole::ACCOUNTANT => [
                ['title' => __('php/mail/owner_team_invite_mail.earnings_title'), 'desc' => __('php/mail/owner_team_invite_mail.earnings_desc')],
                ['title' => __('php/mail/owner_team_invite_mail.documents_title'), 'desc' => __('php/mail/owner_team_invite_mail.documents_desc')],
                ['title' => __('php/mail/owner_team_invite_mail.exports_title'), 'desc' => __('php/mail/owner_team_invite_mail.exports_desc')],
            ],
            OwnerTeamRole::VIEW_ONLY => [
                ['title' => __('php/mail/owner_team_invite_mail.earnings_title'), 'desc' => __('php/mail/owner_team_invite_mail.earnings_desc')],
                ['title' => __('php/mail/owner_team_invite_mail.calendar_title'), 'desc' => __('php/mail/owner_team_invite_mail.calendar_desc')],
            ],
            default => [
                ['title' => __('php/mail/owner_team_invite_mail.earnings_title'), 'desc' => __('php/mail/owner_team_invite_mail.earnings_desc')],
            ],
        };
    }
}
