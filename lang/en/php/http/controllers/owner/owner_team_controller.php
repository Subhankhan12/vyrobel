<?php

// app/Http/Controllers/Owner/OwnerTeamController.php — the owner team writes (flash messages, the re-auth field name).

return [
    // the field's name inside Laravel's validation messages ("The current password field is required.")
    'attr_current_password' => 'current password',
    'already_on_team' => 'That person is already on your team.',
    'invite_sent' => 'Invite sent to :email.',
    'role_updated' => 'Role updated.',
    'member_removed' => 'Member removed.',
    'invite_revoked' => 'Invite revoked.',
    'invite_resent' => 'Invite re-sent to :email.',
];
