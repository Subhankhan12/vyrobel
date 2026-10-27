<?php

// app/Http/Middleware/EnsureSubscriptionActive.php — the closed-workspace page for someone who cannot renew (and its JSON refusal).

return [
    'ended_json' => 'This workspace’s subscription has ended.',
    'title' => 'This workspace’s subscription has ended',
    'message' => ':organization’s Vyrobel trial or subscription has ended, so the back office is closed until it is renewed. Nothing has been deleted — reservations, guests and payments are kept exactly as they were. Only someone who manages billing for this workspace can renew it, so please ask your administrator.',
];
