<?php

// resources/views/livewire/owner/notifications.blade.php — the owner's notification feed.

return [
    'title' => 'Notifications',
    'subtitle' => ':number unread',
    'mark_all_read' => 'Mark all read',
    // screen-reader text beside a notification's unread dot
    'unread_state' => 'Unread',
    'mark_read' => 'Mark read',
    // :title is the notification's own title
    'mark_read_aria' => 'Mark “:title” as read',
    'dismiss' => 'Dismiss',
    'dismiss_aria' => 'Dismiss “:title”',
    'empty' => 'All caught up — no notifications.',
    // the word the pager uses for what it counts ("1–20 of 45 notifications")
    'pager_label' => 'notifications',
    'preferences_note' => 'Choose what we tell you and where in <a href=":url" style="color:var(--vy-accent-tint-text)">Notification preferences</a>. Statements are always kept in your portal regardless.',
];
