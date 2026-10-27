<?php

// resources/views/livewire/owner/owner-security.blade.php — the owner Security page (/owner/security) and its dialogs.

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
    // :when is how long ago ("3 hours ago")
    'signing_in_sub_last' => 'How you prove it’s you. Last sign-in :when.',
    'password_heading' => 'Password',
    'password_none' => 'No password yet — set one to sign in with your email address.',
    // :when is how long ago ("3 days ago")
    'password_changed' => 'Changed :when.',
    'password_not_recorded' => 'Not recorded yet — it was set before Vyrobel kept the date.',
    'change_button' => 'Change',
    'set_password_button' => 'Set password',
    // your data
    'data_heading' => 'Data & privacy',
    'data_sub' => 'Download a copy of your data — your profile plus the statements, payouts and units your account covers.',
    'request_my_data' => 'Request my data',
    // deactivate
    'deactivate_heading' => 'Deactivate account',
    'deactivate_sub' => 'Pause your portal access. Your statements and payout history are <strong>kept</strong> — nothing is deleted. To reactivate, contact your management team.',
    'deactivate_button' => 'Deactivate',
    // sign-in protection
    'protection_heading' => 'Sign-in protection',
    'login_approval' => 'Login approval requests',
    'login_approval_meta' => 'A new device waits until you approve it — from the link we email you, or here from a device you’re signed in on. A trusted device skips this.',
    'approval_no_email_on' => 'Email isn’t being sent from Vyrobel yet, so a new device can only be approved from here, while you’re signed in somewhere. Turn this off if that could lock you out.',
    'approval_no_email_off' => 'Email isn’t being sent from Vyrobel yet, so an approval link couldn’t reach you. This stays off until it is.',
    'trusted_devices_meta' => 'Skip the two-factor code at sign-in. Tick “Trust this device” at the code screen to add the one you’re on.',
    'this_device' => 'This device',
    // a trusted device's line: :ip is its address, :last_used and :expires are dates (or —)
    'trusted_meta_ip' => ':ip · last used :last_used · trust expires :expires',
    'trusted_meta' => 'last used :last_used · trust expires :expires',
    'no_trusted_devices' => 'No trusted devices',
    'no_trusted_2fa_on' => 'Every sign-in asks for your code.',
    'no_trusted_2fa_off' => 'Two-factor is off, so no device skips it.',
    // active sessions
    'sessions_heading' => 'Active sessions',
    'sessions_sub' => 'Where you’re signed in right now.',
    // :ip is the session's address, :when how long ago it was active
    'session_meta' => '<span class="vy-sx-mono">:ip</span> · active :when',
    'no_sessions' => 'No active sessions recorded',
    'sign_out_others_all' => 'Sign out all other sessions',
    // connected apps
    'apps_sub' => 'Apps you’ve given read-only access — never your guests’ contact details, door codes or Wi-Fi, and only your own data.',
    // follows the app's scope chips; :when is the date it was authorized
    'authorized_at' => '· authorized :when',
    'revoke' => 'Revoke',
    'no_connected_apps' => 'No connected apps',
    'no_connected_apps_hint' => 'Third-party apps you authorize will appear here.',
    // the password dialog
    'change_password_title' => 'Change password',
    'set_password_title' => 'Set a password',
    'change_password_sub' => 'Other devices with “remember me” are signed out.',
    'set_password_sub' => 'You’ll be asked for it when confirming sensitive changes.',
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
    'approval_on_sub' => 'A new device will wait until you approve it from the link we email you.',
    'your_password' => 'Your password',
    'turn_off' => 'Turn off',
    'turn_on' => 'Turn on',
    // the sign-out dialog
    'sign_out_sub' => 'Every session but this one ends now. Confirm with your password.',
    'sign_out_others' => 'Sign out others',
    // the remove-trusted-device dialog
    'remove_device_title' => 'Remove trusted device',
    'remove_device_sub' => 'It will be asked for a two-factor code next time. Confirm with your password.',
    // the revoke-app dialog: :app is the app's name
    'revoke_app_title' => 'Revoke :app?',
    'revoke_app_sub' => 'Its access stops immediately. Confirm with your password.',
    // the deactivate dialog
    'deactivate_title' => 'Deactivate your account?',
    'deactivate_dialog_sub' => 'You’ll be signed out and can’t sign in until it’s reactivated. Your statements and payout history are kept.',
    'keep_my_account' => 'Keep my account',
];
