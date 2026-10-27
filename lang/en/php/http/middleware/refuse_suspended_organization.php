<?php

// app/Http/Middleware/RefuseSuspendedOrganization.php — the suspended-workspace page (and its JSON refusal).

return [
    'suspended_json' => 'Access to this organisation’s Vyrobel workspace is suspended.',
    'title' => 'This workspace is suspended',
    'message_named' => ':organization’s Vyrobel workspace has been suspended, so the back office and the owner portal can’t be opened right now. Nothing has been deleted — reservations, guests and payments are kept exactly as they were, and guests can still reach their own bookings. To find out why, or to restore access, contact Vyrobel.',
    'message' => 'This Vyrobel workspace has been suspended, so the back office and the owner portal can’t be opened right now. Nothing has been deleted — reservations, guests and payments are kept exactly as they were, and guests can still reach their own bookings. To find out why, or to restore access, contact Vyrobel.',
];
