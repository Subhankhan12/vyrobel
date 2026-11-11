<?php

// resources/views/partials/live-pulse.blade.php — the desktop (system) notification a back-office tab can show. It says HOW MANY
// messages are waiting, never who wrote or what: the pulse carries counts only.

return [
    'desktop_title' => 'Messages',
    // :count is how many team and guest messages are waiting
    'desktop_unread' => '{1} :count unread message|[0,*] :count unread messages',
    'desktop_blocked' => 'Notifications are blocked in this browser — allow them in the site settings, then try again.',
    'desktop_unsupported' => 'This browser cannot show desktop notifications.',
];
