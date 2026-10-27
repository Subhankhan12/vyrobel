<?php

// resources/views/components/auth/passkeys.blade.php — the shared passkey section of both Security pages.

return [
    'heading' => 'Passkeys',
    'intro' => 'Sign in with Face ID, Touch ID or your device PIN instead of typing a password. Add one on each device you use.',
    'add_aria' => 'Add a passkey',
    'add_button' => '+ Add',
    'working' => 'Working…',
    // :when is already a readable time ("3 days ago")
    'last_used' => 'Last used :when',
    'empty' => 'No passkeys yet. Add one to sign in without a password on this device.',
    'unsupported' => 'This browser doesn’t support passkeys.',
    'register_failed' => 'That passkey could not be registered. Try again.',
    'register_cancelled' => 'Passkey registration was cancelled or failed.',
];
