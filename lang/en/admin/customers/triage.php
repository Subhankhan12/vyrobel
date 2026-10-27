<?php

// resources/views/admin/customers/triage.blade.php — the admin console's triage queue (organisations worth a look).

return [
    'title' => 'Triage',
    'subtitle' => 'Organisations worth a look — counted, defined, and never acted on automatically.',
    'scope_including_internal' => 'Including Vyrobel’s own internal organisations.',
    'scope_customers_only' => 'Customers only — Vyrobel’s own internal organisations are left out.',
    'toggle_customers_only' => 'Customers only',
    'toggle_include_internal' => 'Include internal',
    'view_in_directory' => 'View in the directory →',
    'no_customers_yet' => '<strong>No customers yet</strong> — every organisation on the platform is one of Vyrobel’s own, so these queues are empty by definition, not because everything is fine.',
];
