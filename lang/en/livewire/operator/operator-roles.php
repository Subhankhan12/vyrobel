<?php

// resources/views/livewire/operator/operator-roles.blade.php — Roles & permissions (/app/roles).

return [
    'title' => 'Roles & permissions',
    'subtitle' => 'What each role can see and do',
    'read_only_banner' => 'Your workspace is read-only right now — role changes are paused.',
    'empty_heading' => 'No editable roles',
    'empty_body' => 'Your organization\'s back-office roles will appear here.',
    'roles_heading' => 'Roles',
    'new_role' => 'New role',
    // :role is the selected role's name (filled in on the page)
    'permissions_heading' => ':role · permissions',
    // :granted and :total are filled in on the page as the toggles change
    'granted_of_total' => ':granted of :total on',
    'matrix_hint' => 'Toggle what this role can access. Changes apply to everyone assigned to it.',
    'confirm_password' => 'Confirm with your password',
    'current_password' => 'Current password',
    'needs_roles_manage' => 'Editing a role\'s permissions needs the organization-admin permission (roles.manage).',
    'selected_role' => 'Selected role',
    'members_heading' => 'Members',
    'no_members' => 'No one holds this role yet.',
    'delete_role' => 'Delete this role',
    'new_role_heading' => 'The role catalogue is fixed',
    'new_role_body' => 'Back-office roles are anchored to how the app grants access, so new roles aren\'t created here. Instead, <strong>edit an existing role\'s permissions</strong> above to shape exactly what its members can do — the toggles take effect immediately.',
    'got_it' => 'Got it',
    'delete_role_heading' => 'Roles can\'t be deleted here',
    'delete_role_body' => 'The back-office role catalogue is fixed. To take access away from a role, un-toggle its permissions above; to move a person off a role, revoke it from them in the assignment section — they keep their other roles.',
    'assign_heading' => 'Assign a role',
    'team_member' => 'Team member',
    'choose_member' => 'Choose a member…',
    'role_label' => 'Role',
    'choose_role' => 'Choose a role…',
    'assign_button' => 'Assign role',
    'assign_hint' => 'Only roles whose access you already hold are listed — you can\'t grant more than your own.',
    'revoke_heading' => 'Revoke a role',
    'revoke_hint' => 'Removing a role takes access away immediately, and the person cannot undo it themselves. They keep any other roles they hold.',
    // shown after the signed-in person's own name in the list
    'you_marker' => '· You',
    'read_only_short' => 'Your workspace is read-only right now.',
    'self_note' => 'Ask another administrator to change your own roles.',
    'not_manageable' => 'This role holds access you don\'t have yourself.',
    'revoke_summary' => 'Revoke this role…',
    'revoke_confirm' => '<strong>:name</strong> will lose the <strong>:role</strong> role and everything it grants. They keep any other roles they hold.',
    'revoke_button' => 'Revoke :role',
    'no_assignments' => 'No one holds a back-office role yet.',
];
