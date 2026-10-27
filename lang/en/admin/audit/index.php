<?php

// resources/views/admin/audit/index.blade.php — the platform-admin audit log.

return [
    'title' => 'Audit log',
    'subtitle' => 'Every console action — who, when, what, on what, and why. Rows are never edited or deleted.',
    'kind_aria' => 'Kind',
    'kind_changes' => 'Changes & sign-ins',
    'kind_reads' => 'Page views & reads',
    'kind_all' => 'Everything',
    'admin_aria' => 'Admin',
    'any_admin' => 'Any admin',
    'action_aria' => 'Action',
    'any_action' => 'Any action',
    'organisation_aria' => 'Organisation',
    'any_organisation' => 'Any organisation',
    'internal_suffix' => ' (internal)',
    'from_aria' => 'From',
    'to_aria' => 'To',
    'empty_state' => 'Nothing matches these filters.',
    'col_when' => 'When (UTC)',
    'col_admin' => 'Admin',
    'col_action' => 'Action',
    'col_target' => 'Target',
    'col_reason' => 'Reason',
    'col_ip' => 'IP',
    'removed_admin' => 'Removed admin',
    'system_cli' => 'System / CLI',
    'target_admin' => 'Admin: :name',
];
