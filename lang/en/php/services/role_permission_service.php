<?php

// app/Services/RolePermissionService.php — the refusals the operator sees when saving a role's permissions.

return [
    'not_editable' => 'That role’s permissions can’t be edited here.',
    // the subset rule, one sentence per direction
    'cannot_grant_unheld' => 'You can’t grant a permission you don’t hold yourself.',
    'cannot_revoke_unheld' => 'You can’t revoke a permission you don’t hold yourself.',
    'last_role_manager' => 'At least one role must keep permission-management access — this would lock the workspace out.',
    'role_not_set_up' => 'That role isn’t set up for your organization.',
];
