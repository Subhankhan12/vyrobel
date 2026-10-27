<?php

// resources/views/livewire/operator/operator-security.blade.php — the operator Security page (/app/security) and its dialogs.

return [
    // the hero's tiles
    'tile_protections_on' => 'Protections on',
    'tile_points_available' => 'Points available',
    'trusted_devices' => 'Trusted devices',
    'connected_apps' => 'Connected apps',
    'page_title' => 'Security',
    // signing in
    'signing_in_heading' => 'Signing in',
    'signing_in_sub' => 'How you prove it’s you.',
    'password_heading' => 'Password',
    'password_none' => 'No password yet — set one to sign in with your email address.',
    // :when is how long ago ("3 days ago")
    'password_changed' => 'Changed :when.',
    'password_not_recorded' => 'Not recorded yet — it was set before Vyrobel kept the date.',
    'change_button' => 'Change',
    'set_password_button' => 'Set password',
    // sign-in accounts (Google, X)
    'accounts_heading' => 'Sign-in accounts',
    'accounts_sub' => 'Sign in to Vyrobel with an account you already have.',
    // a linked account's line: :email is the account's address, :when how long ago it was used (or "not used yet")
    'connection_meta_email' => ':email · last used :when',
    'connection_meta' => 'last used :when',
    'disconnect' => 'Disconnect',
    'none_connected' => 'None connected',
    'accounts_off' => 'Signing in with Google or X isn’t switched on here yet.',
    'accounts_connect_hint' => 'Connect one to sign in without typing your password.',
    // :provider is the sign-in provider's name (Google, X)
    'connect_provider' => 'Connect :provider',
    // active sessions
    'sessions_heading' => 'Active sessions & devices',
    'sessions_sub' => 'Where your account is signed in now.',
    'this_device' => 'This device',
    // a session's line: :ip is its address
    'session_meta_now' => '<span class="vy-sx-mono">:ip</span> · active now',
    // :when is how long ago it was last active
    'session_meta_last' => '<span class="vy-sx-mono">:ip</span> · last active :when',
    'sign_out_session' => 'Sign out',
    'no_sessions' => 'No sessions recorded',
    'sign_out_all_others' => 'Sign out of all other devices',
    // your data
    'data_heading' => 'Data & privacy',
    'data_sub' => 'Download a copy of your account details and the actions you’ve taken.',
    'export_my_data' => 'Export my data',
    // sign-in protection
    'protection_heading' => 'Sign-in protection',
    'login_approval' => 'Login approval requests',
    'login_approval_meta' => 'A new device waits until you approve it — from the link we email you, or here from a device you’re signed in on. A trusted device skips this.',
    'approval_no_email_on' => 'Email isn’t being sent from Vyrobel yet, so a new device can only be approved from here, while you’re signed in somewhere. Turn this off if that could lock you out.',
    'approval_no_email_off' => 'Email isn’t being sent from Vyrobel yet, so an approval link couldn’t reach you. This stays off until it is.',
    'trusted_devices_meta' => 'Skip the two-factor code for 30 days. Revoke any you don’t recognise.',
    // :when is a date or how long ago
    'trusted_last_used' => 'Last used :when',
    'revoke' => 'Revoke',
    'no_trusted_devices' => 'No trusted devices',
    'no_trusted_2fa_on' => 'Every sign-in asks for your code.',
    'no_trusted_2fa_off' => 'Two-factor is off, so no device skips it.',
    // the IP allowlist
    'ip_heading' => 'IP allowlist',
    'ip_sub' => 'When set, only these addresses can reach the back office — for everyone in this workspace.',
    'add_ip' => '+ Add IP',
    'no_ip_rules' => 'No IP restrictions',
    'no_ip_rules_hint' => 'Anyone with a valid sign-in can reach the back office from any network.',
    'ip_needs_permission' => 'Changing the allowlist needs the “manage settings” permission.',
    // connected apps
    'apps_sub' => 'Third-party apps you’ve allowed into your account.',
    'no_apps' => 'No apps connected',
    'no_apps_hint' => 'Third-party app access isn’t available for staff accounts yet.',
    // the password dialog
    'change_password_title' => 'Change password',
    'set_password_title' => 'Set a password',
    'change_password_sub' => 'You stay signed in here.',
    'set_password_sub' => 'Set one to sign in with your email address.',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'confirm_new_password' => 'Confirm new password',
    // the show/hide toggle's name for each field ("Show current password")
    'field_current_password' => 'current password',
    'field_new_password' => 'new password',
    'field_password_confirmation' => 'password confirmation',
    'field_password' => 'password',
    'update_password' => 'Update password',
    // the login approval dialog
    'approval_off_title' => 'Turn off login approval',
    'approval_on_title' => 'Turn on login approval',
    'approval_off_sub' => 'New devices will sign in without waiting for your approval.',
    'approval_on_sub' => 'A new device will wait until you approve it.',
    'your_password' => 'Your password',
    'turn_off' => 'Turn off',
    'turn_on' => 'Turn on',
    // the add-IP dialog: :ip is the address the person is on now
    'add_ip_title' => 'Add to the allowlist',
    'add_ip_sub' => 'One address, or a block of them. Your address now is <strong>:ip</strong>.',
    'ip_address_label' => 'Address',
    'ip_address_placeholder' => '203.0.113.4 or 203.0.113.0/24',
    'ip_label_label' => 'Label · optional',
    'ip_label_placeholder' => 'Office',
    'add_ip_note' => 'When this is the first rule, your own address is added too, so you are never locked out.',
    'add_to_allowlist' => 'Add to allowlist',
    // the remove-IP dialog: :ip is the address being removed (filled in on the page)
    'remove_ip_title' => 'Remove :ip?',
    'remove_ip_sub' => 'Devices at this address lose access unless another rule covers them.',
    'remove_ip_note' => 'If it is the only rule that lets your own address in, it is kept — removing it would lock you out. Removing the last rule turns the allowlist off.',
    // the export dialog
    'export_title' => 'Export your data',
    'export_sub' => 'Your account details and the actions you’ve taken. Downloads to this device.',
    'export_format' => 'Format',
    'export_range' => 'Date range',
    'range_all_time' => 'All time',
    'range_this_year' => 'This year',
    'range_custom' => 'Custom',
    'range_from' => 'From',
    'range_to' => 'To',
    // the sign-out dialog
    'sign_out_sub' => 'Every session but this one ends now. Confirm with your password.',
    'sign_out_others' => 'Sign out others',
    // the revoke-trusted-device dialog
    'revoke_device_title' => 'Revoke trusted device',
    'revoke_device_sub' => 'It will be asked for a two-factor code next time. Confirm with your password.',
];
