<?php

// resources/views/admin/status.blade.php — the platform admin console's Status page (incidents on the public status page).

return [
    'title' => 'Status page',
    'subtitle' => 'Incidents on the public status page — posted by hand. Nothing internal ever reaches it.',
    // :url is the public status page's address, shown as the link text too
    'public_warning' => '<strong>Everything written here is public</strong> at <a class="ad-cell-link" href=":url" target="_blank" rel="noopener">:url</a>.',
    'shows_only_posted' => 'The page shows only what is posted: with nothing posted it says “No incidents have been posted” — never “all systems operational”.',
    'none_ever_posted' => 'No incident has ever been posted.',
    'started' => 'Started',
    'resolved' => 'resolved',
    'status_aria' => 'Status',
    'public_message_aria' => 'Public message',
    'public_update_placeholder' => 'Public update',
    'reason_aria' => 'Reason',
    'internal_reason_placeholder' => 'Internal reason (audit log)',
    'post_update' => 'Post update',
    'post_incident_heading' => 'Post an incident',
    'public_title' => 'Public title',
    'impact' => 'Impact',
    'public_message' => 'Public message',
    'internal_reason_label' => 'Internal reason (audit log — not public)',
    'post_publicly' => 'Post publicly',
];
