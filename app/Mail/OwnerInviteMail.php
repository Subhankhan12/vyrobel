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
        return new Envelope(subject: __('php/mail/owner_invite_mail.subject'));
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
                // ⚠️ ADMIN 04 — a founder's workspace is set up by VYROBEL, not by the hotel: the footer's "you're
                //    receiving this because …" must name who actually sent it.
                'operator' => $copy['operator'] ?? $orgName,
                'footerNote' => $copy['footer_note'] ?? __('php/mail/owner_invite_mail.footer_note'),
                'ignoreNote' => $copy['ignore_note'] ?? __('php/mail/owner_invite_mail.ignore_note'),
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
     * @return array{account: string, eyebrow: string, headline: string, intro: string, section_title: string, features: list<array{title: string, desc: string}>, role_reason: string, operator?: string, footer_note?: string, ignore_note?: string}
     */
    private function copyFor(string $role, string $orgName): array
    {
        return match ($role) {
            // ADMIN 04 — the founder of an organisation created in the platform console (OwnerInviteService::FOUNDER_ROLE).
            'founder' => [
                'account' => __('php/mail/owner_invite_mail.founder_account'),
                'eyebrow' => __('php/mail/owner_invite_mail.founder_eyebrow'),
                'headline' => __('php/mail/owner_invite_mail.founder_headline', ['org' => $orgName]),
                'intro' => __('php/mail/owner_invite_mail.founder_intro', ['org' => $orgName]),
                'section_title' => __('php/mail/owner_invite_mail.founder_section_title'),
                'features' => [
                    ['title' => __('php/mail/owner_invite_mail.founder_rooms_title'), 'desc' => __('php/mail/owner_invite_mail.founder_rooms_desc')],
                    ['title' => __('php/mail/owner_invite_mail.founder_rates_title'), 'desc' => __('php/mail/owner_invite_mail.founder_rates_desc')],
                    ['title' => __('php/mail/owner_invite_mail.founder_bookings_title'), 'desc' => __('php/mail/owner_invite_mail.founder_bookings_desc')],
                ],
                'operator' => 'Vyrobel',
                'role_reason' => __('php/mail/owner_invite_mail.founder_role_reason', ['org' => $orgName]),
                'footer_note' => __('php/mail/owner_invite_mail.founder_footer_note'),
                // ⚠️ the workspace ALREADY exists for a founder — the standard "no account is created" line would be false
                'ignore_note' => __('php/mail/owner_invite_mail.founder_ignore_note'),
            ],
            'manager' => [
                'account' => __('php/mail/owner_invite_mail.manager_account'),
                'eyebrow' => __('php/mail/owner_invite_mail.manager_eyebrow'),
                'headline' => __('php/mail/owner_invite_mail.manager_headline', ['org' => $orgName]),
                'intro' => __('php/mail/owner_invite_mail.manager_intro', ['org' => $orgName]),
                'section_title' => __('php/mail/owner_invite_mail.manager_section_title'),
                'features' => [
                    ['title' => __('php/mail/owner_invite_mail.manager_calendar_title'), 'desc' => __('php/mail/owner_invite_mail.manager_calendar_desc')],
                    ['title' => __('php/mail/owner_invite_mail.manager_housekeeping_title'), 'desc' => __('php/mail/owner_invite_mail.manager_housekeeping_desc')],
                    ['title' => __('php/mail/owner_invite_mail.manager_reservations_title'), 'desc' => __('php/mail/owner_invite_mail.manager_reservations_desc')],
                ],
                'role_reason' => __('php/mail/owner_invite_mail.manager_role_reason'),
            ],
            'accountant' => [
                'account' => __('php/mail/owner_invite_mail.accountant_account'),
                'eyebrow' => __('php/mail/owner_invite_mail.accountant_eyebrow'),
                'headline' => __('php/mail/owner_invite_mail.accountant_headline', ['org' => $orgName]),
                'intro' => __('php/mail/owner_invite_mail.accountant_intro', ['org' => $orgName]),
                'section_title' => __('php/mail/owner_invite_mail.accountant_section_title'),
                'features' => [
                    ['title' => __('php/mail/owner_invite_mail.accountant_statements_title'), 'desc' => __('php/mail/owner_invite_mail.accountant_statements_desc')],
                    ['title' => __('php/mail/owner_invite_mail.accountant_tax_title'), 'desc' => __('php/mail/owner_invite_mail.accountant_tax_desc')],
                    ['title' => __('php/mail/owner_invite_mail.accountant_payouts_title'), 'desc' => __('php/mail/owner_invite_mail.accountant_payouts_desc')],
                ],
                'role_reason' => __('php/mail/owner_invite_mail.accountant_role_reason'),
            ],
            default => [
                'account' => __('php/mail/owner_invite_mail.owner_account'),
                'eyebrow' => __('php/mail/owner_invite_mail.owner_eyebrow'),
                'headline' => __('php/mail/owner_invite_mail.owner_headline', ['org' => $orgName]),
                'intro' => __('php/mail/owner_invite_mail.owner_intro', ['org' => $orgName]),
                'section_title' => __('php/mail/owner_invite_mail.owner_section_title'),
                'features' => [
                    ['title' => __('php/mail/owner_invite_mail.owner_statements_title'), 'desc' => __('php/mail/owner_invite_mail.owner_statements_desc')],
                    ['title' => __('php/mail/owner_invite_mail.owner_payouts_title'), 'desc' => __('php/mail/owner_invite_mail.owner_payouts_desc')],
                    ['title' => __('php/mail/owner_invite_mail.owner_performance_title'), 'desc' => __('php/mail/owner_invite_mail.owner_performance_desc')],
                ],
                'role_reason' => __('php/mail/owner_invite_mail.owner_role_reason'),
            ],
        };
    }
}
