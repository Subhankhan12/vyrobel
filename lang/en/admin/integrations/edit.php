<?php

// resources/views/admin/integrations/edit.blade.php — manage one channel's master credential (write-only secret).

return [
    'subtitle_configured' => 'A master credential is configured (encrypted). Enter a new value below only to rotate it.',
    'subtitle_none' => 'No credential yet. Add your platform master credential for :channel.',
    'back_label' => 'All integrations',
    'master_credential' => 'Master credential',
    'api_key' => 'API key',
    'api_key_placeholder_configured' => '•••••••• (enter a new value to rotate)',
    'api_key_placeholder' => 'Enter the API key',
    // the field name and its "· optional" note stay one phrase so each language orders them its own way
    'api_secret' => 'API secret <span style="text-transform: none; letter-spacing: 0; color: var(--ad-faint);">· optional</span>',
    'api_secret_placeholder_configured' => '•••••••• (leave blank to keep)',
    'api_secret_placeholder' => 'Enter the API secret',
    'enabled' => 'Enabled',
    'stored_encrypted' => '🔒 Stored encrypted at rest. The secret is never displayed again — you can only replace it.',
    'reason_placeholder' => 'Why is this credential being set or rotated?',
    'save_credential' => 'Save credential',
    'test_reason_placeholder' => 'Reason for the test',
    'test_reason_aria' => 'Reason',
    'test_connection' => 'Test connection',
    'test_hint' => 'Verifies a credential is present; the live test runs when the :channel account is connected.',
];
