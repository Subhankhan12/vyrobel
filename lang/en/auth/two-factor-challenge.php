<?php

// resources/views/auth/two-factor-challenge.blade.php — the two-step verification code after the password.

return [
    'page_title' => 'Two-step verification · Vyrobel',
    'heading' => 'Two-step verification',
    'subheading' => 'Enter the 6-digit code from your authenticator app to finish signing in.',
    'code_label' => 'Authentication code',
    'trust_device' => 'Trust this device for 30 days (skip codes here)',
    'verify' => 'Verify',
    'lost_device' => 'Lost your device? Enter one of your <strong>recovery codes</strong> above instead. Or <a href=":url">start over</a>.',
    'footer' => 'Vyrobel · secure sign-in',
];
