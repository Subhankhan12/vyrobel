<?php

// app/Mail/OwnerTeamInviteMail.php — the owner-team invite: the copy the Mailable builds for the shared owner-invite view.
// :role is the team role's name, :org the organisation's name.

return [
    'subject' => 'You’re invited to a Vyrobel owner team',
    // printed inside the view's sentence "activate your Vyrobel …"
    'account_label' => 'owner-team account',
    'eyebrow' => ':role invitation',
    'headline' => 'Your :role access on :org',
    'intro' => 'You’ve been invited to join an owner team on :org in the Vyrobel owner portal as :role. Set your password to activate your account and get started.',
    'section_title' => 'What you can access',
    // printed after the organisation's name in the view's "You’re receiving this because …"
    'role_reason' => 'invited you to their owner team as :role.',
    // what each role can reach (from the real capability matrix)
    'earnings_title' => 'Earnings & statements',
    'earnings_desc' => 'Every booking, fee and payout, itemised',
    'calendar_title' => 'Calendar, bookings & units',
    'calendar_desc' => 'How the properties are performing, night by night',
    'approvals_title' => 'Approvals & payout settings',
    'approvals_desc' => 'Blocks, rates and requests — and where the money goes',
    'documents_title' => 'Documents & tax',
    'documents_desc' => 'Statements, annual summaries and VAT records',
    'exports_title' => 'Exports',
    'exports_desc' => 'Download what you need — read-only, no approvals',
];
