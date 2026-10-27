<?php

// resources/views/status.blade.php — the public status page (incidents posted by an admin).

return [
    'page_title' => 'Status · Vyrobel',
    'heading' => 'Vyrobel status',
    'intro' => 'Incidents posted by the Vyrobel team. Times are UTC.',
    'when_resolved' => 'Started :started · resolved :resolved',
    'when_open' => 'Started :started · last update :updated',
    'no_incidents' => 'No incidents have been posted.',
];
