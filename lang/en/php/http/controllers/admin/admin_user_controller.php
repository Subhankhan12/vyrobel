<?php

// app/Http/Controllers/Admin/AdminUserController.php — the console's own team: invite, role, revoke, restore, sign out.
// :role is the role's own label; :name is the admin's name as stored.

return [
    'only_pending_reissued' => 'Only a pending invitation can be re-issued.',
    'last_owner_role' => 'This is the last active Owner. Make someone else an Owner first.',
    'role_changed' => ':name is now :role.',
    'cannot_revoke_self' => 'You cannot revoke your own access. Ask another Owner.',
    'last_owner_revoke' => 'This is the last active Owner and cannot be revoked.',
    'access_revoked' => "{1} :name's access is revoked; :count session ended.|[0,*] :name's access is revoked; :count sessions ended.",
    'only_revoked_restored' => 'Only a revoked admin can be restored.',
    'access_restored' => ":name's access is restored.",
    'ended_own_other_sessions' => '{1} Ended :count of your other session — this one stays signed in.|[0,*] Ended :count of your other sessions — this one stays signed in.',
    'ended_sessions_for' => '{1} Ended :count session for :name.|[0,*] Ended :count sessions for :name.',
];
