<?php

// resources/views/admin/auth/two-factor-challenge.blade.php — the admin console's two-factor sign-in challenge.

return [
    'title' => 'Two-factor',
    'heading' => 'Verify it\'s you',
    'sub' => 'Enter the 6-digit code from your authenticator app.',
    'code_label' => 'Authentication code',
    'code_placeholder' => '000000',
    'verify_button' => 'Verify',
    'lost_device_note' => 'Lost your device? Enter one of your recovery codes instead.',
];
