<?php

// resources/views/components/notifications/bell.blade.php — the top-bar bell and its notifications panel.

return [
    'notifications' => 'Notifications',
    'notifications_unread' => 'Notifications (:count unread)',
    'unread_count' => ':count unread',
    // the uppercase category tag on each row, by the category's code
    'categories' => [
        'general' => 'General',
        'finance' => 'Finance',
        'front_desk' => 'Front Desk',
        'maintenance' => 'Maintenance',
        'reputation' => 'Reputation',
        'account' => 'Account',
        'system' => 'System',
        'messages' => 'Messages',
        'operations' => 'Operations',
        'performance' => 'Performance',
        'trips' => 'Trips',
        'guest_id' => 'Guest Id',
    ],
    'unread' => 'Unread',
    'empty' => 'All caught up — no notifications.',
    'mark_all_read' => 'Mark all read',
    'open_all' => 'Open all notifications →',
];
