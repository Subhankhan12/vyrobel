<?php

// app/Notifications/Operator/SupportAccessNotification.php — a Vyrobel support session in the workspace.
// :reason is the admin's own words; :from / :to the session's times in UTC.

return [
    'title' => 'Vyrobel support viewed your workspace (read-only)',
    // one key per page-count shape, because the feed records a key, not a choice
    'context_one_page' => ':admin · :from–:to UTC · :pages page viewed · Reason: :reason',
    'context_pages' => ':admin · :from–:to UTC · :pages pages viewed · Reason: :reason',
];
