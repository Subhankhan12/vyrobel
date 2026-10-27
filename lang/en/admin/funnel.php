<?php

// resources/views/admin/funnel.blade.php — the admin console's signup funnel, split by signup source.

return [
    'col_all' => 'All',
    'col_console' => 'Console',
    'col_unknown' => 'Unknown',
    'title' => 'Signup funnel',
    'subtitle' => 'Where organisations get to, split by how they signed up — from what is recorded, nothing invented.',
    'scope_including_internal' => 'Including Vyrobel’s own internal organisations.',
    'scope_customers_only' => 'Customers only.',
    'toggle_customers_only' => 'Customers only',
    'toggle_include_internal' => 'Include internal',
    'no_customers_yet' => '<strong>No customers yet</strong> — every organisation on the platform is one of Vyrobel’s own, so the funnel is empty by definition. Include internal organisations to see the machinery work.',
    'th_stage' => 'Stage',
    'unmeasured_heading' => 'Not measured — and why',
    'unmeasured_pill' => 'Unmeasured',
    'not_a_stage_heading' => 'Not a stage',
    'not_a_stage_intro' => '“Added a property” — sign-up creates the first property in the same step, so every organisation starts with one. Measured, not assumed:',
    'without_property' => 'Organisations with no property',
    'unknown_hint' => '“Unknown” is an organisation created before the sign-up source was recorded — not a guess that it came by email.',
];
