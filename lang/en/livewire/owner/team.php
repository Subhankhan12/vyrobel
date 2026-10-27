<?php

// resources/views/livewire/owner/team.blade.php — the owner's Team & access page (members, invites, role matrix, download log).

return [
    'title' => 'Team & access',
    'subtitle' => 'Give a co-owner or your accountant their own sign-in — scoped to what they need.',
    'people_with_access' => 'People with access',
    'account_owner' => 'Account owner',
    'thats_you' => 'that’s you',
    'owner_access' => 'Full access to everything, including this page.',
    'active_now' => 'Active now',
    // :name is the team member's name
    'role_for' => 'Role for :name',
    'your_password' => 'Your password',
    'confirm_password' => 'Confirm with your password',
    'remove_confirm' => 'Remove :name? They can no longer sign in.',
    'no_members' => 'Just you so far — invite a co-owner or accountant below.',
    'invite_heading' => 'Invite someone',
    'invite_sub' => 'They get their own sign-in — you can remove access anytime.',
    'invite_email_label' => 'Email address to invite',
    'email_placeholder' => 'name@email.com',
    'send_invite' => 'Send invite',
    'pending_invites' => 'Pending invites',
    'resend' => 'Resend',
    'revoke' => 'Revoke',
    'matrix_heading' => 'What each role can see',
    // the three role columns of the matrix are App\Support\Owner\OwnerTeamRole::label() — one definition
    'matrix_note' => 'Only you, the account owner, can manage the team and the payout account.',
    'recent_activity' => 'Recent activity',
    'no_activity' => 'Nothing yet — invites and sign-ins will show up here.',
    'download_log' => 'Export download log',
    'download_log_sub' => 'Every statement, booking, payout or tax file your team downloads is stamped with who downloaded it — and recorded here.',
    'no_downloads' => 'No exports downloaded yet — they’ll appear here, stamped with who downloaded them.',
    'security_heading' => 'Everyone gets their own key',
    'security_body' => 'No shared passwords — each person signs in with their own account, and access is scoped to their role. Financial exports are watermarked with who downloaded them and recorded in the log above. For your own account, turn on 2-step verification.',
    'security_settings' => 'Security settings',
];
