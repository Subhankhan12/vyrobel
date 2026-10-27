<?php

// app/Http/Controllers/Operator/OwnerInviteActionController.php — resend and cancel a waiting owner invite.

return [
    'password_required' => 'Enter your password to send the invite again.',
    'password_wrong' => 'That password isn’t right — enter the one you sign in with.',
    'cannot_resend' => 'That invite can’t be sent again — the email may already have a Vyrobel account.',
    'resent_valid_until' => 'Invite sent again to :email — a fresh link, valid until :date.',
    'resent_valid_7_days' => 'Invite sent again to :email — a fresh link, valid until in 7 days.',
    'already_used_or_cancelled' => 'That invite has already been used or cancelled.',
    'invite_cancelled' => 'Invite to :email cancelled — the link no longer works.',
    'workspace_paused' => 'Workspace paused — invites can’t be sent or cancelled for now.',
];
