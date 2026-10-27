<?php

// resources/views/livewire/operator/notifications.blade.php — the operator's own notification feed.

return [
    'page_title' => 'Notifications',
    // the page subtitle and the unread pill
    'unread_count' => '{1} :count unread|[0,*] :count unread',
    'filter_label' => 'Filter notifications',
    // the filter tab
    'tab_unread' => 'Unread',
    'mark_all_read' => 'Mark all read',
    // a row's state, read by assistive tech
    'unread_state' => 'Unread',
    'mark_read' => 'Mark read',
    'mark_read_aria' => 'Mark “:title” as read',
    'dismiss' => 'Dismiss',
    'dismiss_aria' => 'Dismiss “:title”',
    'empty_state' => 'All caught up — no notifications.',
    // the pager's "1–20 of 45 notifications"
    'pager_label' => 'notifications',
    // the link stays inside the sentence so each language places it where its grammar needs it
    'preferences_hint' => 'Choose which of these reach you in <a href=":url" style="color:var(--vy-accent)">Notification preferences</a>.',
];
