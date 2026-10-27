<?php

// app/Support/Platform/PlatformAdminRole.php — the console roles' display names and descriptions (the stored role codes never change).

return [
    'owner_label' => 'Owner',
    'support_label' => 'Support',
    'billing_label' => 'Billing',
    'analyst_label' => 'Analyst',
    'owner_description' => 'Everything, including switches, integrations and other admins.',
    'support_description' => 'Customers and support tools. No billing, no switches.',
    'billing_description' => 'Revenue, plans and subscriptions. No support tools.',
    'analyst_description' => 'Read-only everywhere it may look. Changes nothing.',
];
