<?php

// resources/views/livewire/partials/two-factor-panel.blade.php — the shared two-factor section of both Security pages.

return [
    'heading' => 'Two-factor authentication',
    'state_on' => 'On',
    'state_setting_up' => 'Setting up',
    'state_off' => 'Off',
    'meta' => 'A 6-digit code from your authenticator app (Google Authenticator, 1Password, …) at each sign-in.',
    'turn_off_switch_label' => 'Two-factor authentication — turn off',
    'enable_label' => 'Enable two-factor',
    'save_codes_heading' => 'Save your recovery codes',
    'save_codes_body' => 'Each code works once if you lose your authenticator device. They won’t be shown again — store them somewhere safe.',
    'recovery_codes' => 'Recovery codes',
    'codes_left' => ':left of :total recovery codes left',
    'none_left' => 'None left — make a new set now',
    'regenerate_button' => 'Regenerate',
    'scan_qr' => 'Scan this QR code in your authenticator app, then enter the 6-digit code it shows to finish.',
    'manual_key' => 'Or enter this key manually',
    'code_label' => '6-digit code from your app',
    'confirm_button' => 'Confirm & turn on',
    'cancel_setup_button' => 'Cancel setup',
];
