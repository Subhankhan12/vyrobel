<?php

// resources/views/admin/plans/create.blade.php — the super-admin form that creates a plan (always as a draft).

return [
    'title' => 'New plan',
    'subtitle' => 'Created as a draft — add its Stripe Price id, then activate it.',
    'back_label' => 'All plans',
    'details_heading' => 'Details',
    'plan_name_label' => 'Plan name',
    'code_label' => 'Code',
    'code_placeholder' => 'e.g. studio_monthly',
    'code_hint' => 'Lowercase, digits and underscores. Permanent.',
    'reason_placeholder' => 'Why is this plan being added?',
    'create_draft_button' => 'Create draft',
];
