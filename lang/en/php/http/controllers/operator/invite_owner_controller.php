<?php

// app/Http/Controllers/Operator/InviteOwnerController.php — the Owners page's "Invite" dialog: invite an owner, a manager or an accountant.

return [
    'workspace_paused' => 'Workspace paused — inviting people is read-only for now.',
    'password_required' => 'Enter your password to send the invite.',
    'password_wrong' => 'That password isn’t right — enter the one you sign in with.',
    'name_required' => 'Enter the person’s or company’s name.',
    'email_required' => 'Enter the email address the invite goes to.',
    'email_invalid' => 'That doesn’t look like an email address.',
    'commission_range' => 'The commission must be a number between 0 and 100.',
    // one unit in a list: the unit's name (or code) and the owner it already has
    'unit_owned_by' => ':unit (owned by :owner)',
    'another_owner' => 'another owner',
    // :units is the list of unit_owned_by items, joined with commas
    'units_already_owned' => '{1} :units already has an owner. A unit’s owner decides who is paid for it, so an invite can only give units that have none.|[0,*] :units already have owners. A unit’s owner decides who is paid for it, so an invite can only give units that have none.',
    'email_cannot_be_invited' => 'That email can’t be invited — it may already have a Vyrobel account.',
    'invite_sent' => 'Invite sent to :email.',
    // D-MAIL-CLAIMS: while email is not sent from Vyrobel
    'invite_saved_no_mail' => 'Invite saved for :email. Email isn’t being sent from Vyrobel yet, so they haven’t been told.',
];
