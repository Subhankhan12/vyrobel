<?php

// resources/views/admin/integrations/index.blade.php — the channel-integration catalog (platform master credentials).

return [
    'title' => 'Integrations',
    'subtitle' => 'Your platform-level master credentials for the booking channels — stored encrypted.',
    'col_channel' => 'Channel',
    'col_credential' => 'Credential',
    'col_last_tested' => 'Last tested',
    'not_set' => 'Not set',
    'status_tested' => 'Tested',
    'status_configured' => 'Configured',
    'status_not_configured' => 'Not configured',
    'enabled' => 'Enabled',
    'manage' => 'Manage',
    'how_customers_connect' => 'How customers connect',
    'how_customers_connect_body' => 'These are the <strong style="color: var(--ad-ink);">platform\'s</strong> master credentials. Later, each customer connects <strong style="color: var(--ad-ink);">their own</strong> channel account on top (the organisation’s own "connect your account" flow) — that per-customer connection, and the live two-way sync, are account-gated and arrive with the real channel accounts. This page manages the platform side.',
];
