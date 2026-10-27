<?php

// resources/views/guest/notifications.blade.php — the guest's own notification feed.

return [
    'title' => 'Notifications',
    // shown only when at least one is unread; English reads the same for every count
    'unread_summary' => ':count unread · updates about your bookings, messages and account.',
    'summary' => 'Updates about your bookings, messages and account.',
    'mark_all_read' => 'Mark all read',
    'unread' => 'Unread',
    'mark_read' => 'Mark read',
    'mark_read_label' => 'Mark “:title” as read',
    'dismiss' => 'Dismiss',
    'dismiss_label' => 'Dismiss “:title”',
    'empty_heading' => 'All caught up',
    'empty_body' => 'You have no notifications yet. Updates about your bookings, messages and account will appear here.',
];
