<?php

// app/Http/Middleware/EnsureOwnerPortalAccess.php — what an owner (or one of their team) sees when an operator has switched the owner's portal access off (DEC-o).

return [
    'portal_off_json' => 'Portal access for this owner account is switched off.',
    'title' => 'Portal access is switched off',
    'message_named' => ':organization has switched off access to the owner portal for this account, for now. Nothing has been deleted — your statements, payouts and records are kept exactly as they were, and payouts are not affected. To have access switched back on, contact :organization.',
    'message' => 'The team that manages your properties has switched off access to the owner portal for this account, for now. Nothing has been deleted — your statements, payouts and records are kept exactly as they were, and payouts are not affected. To have access switched back on, contact them.',
];
