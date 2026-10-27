<?php

// app/Http/Controllers/Operator/OperatorSecurityController.php — the operator Security writes (IP allowlist, sessions, trusted devices) and the CSV account export.

return [
    'ip_invalid' => 'Enter a valid IP address or CIDR block (e.g. 203.0.113.4 or 203.0.113.0/24).',
    'ip_already_listed' => ':ip is already on the allowlist.',
    'allowlist_enabled_auto_added' => 'IP allowlist enabled. We also added your current address (:ip) so you don’t lock yourself out.',
    'ip_added' => 'Added :ip to the allowlist.',
    'remove_would_lock_out' => 'Removing :ip would lock you out — it is the only rule that lets your address (:current) reach the back office. Add a rule for your address first, or remove the others.',
    'ip_removed_allowlist_off' => 'Removed :ip — the allowlist is off: anyone with a valid sign-in can reach the back office.',
    'ip_removed' => 'Removed :ip from the allowlist.',
    'session_already_ended' => 'That session has already ended.',
    'signed_out_of_device' => 'Signed out of :device.',
    'trusted_device_revoked' => 'Trusted device revoked.',
    'csv_title' => 'Vyrobel account export',
    'csv_name' => 'Name',
    'csv_email' => 'Email',
    'csv_locale' => 'Locale',
    'csv_roles' => 'Roles',
    'csv_activity' => 'Activity',
    'csv_when' => 'When',
    'csv_action' => 'Action',
    'range_this_year' => 'This year',
    'range_custom' => 'Custom range',
    'range_all_time' => 'All time',
];
