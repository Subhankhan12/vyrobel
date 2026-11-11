<?php

// app/Http/Controllers/Admin/AccountController.php — the platform admin's own console account: profile, password, 2FA recovery codes, email change.

return [
    'profile_saved' => 'Your profile is saved.',
    'password_changed' => 'Your password has been changed.',
    // follows password_changed as its own sentence; only shown when at least one session was ended
    'other_sessions_signed_out' => '{1} :count other console session was signed out.|[2,*] :count other console sessions were signed out.',
    'two_factor_not_active' => 'Two-factor is not active yet.',
    'recovery_codes_generated' => 'New recovery codes generated — save them now.',
    // the field's name inside a validation message
    'email_attribute' => 'email address',
    // D-MAIL-CLAIMS: while email is not sent from Vyrobel the confirmation link could never arrive
    'email_change_no_mail' => 'Email isn’t being sent from Vyrobel yet, so we can’t send the link that confirms a new address. Nothing was changed.',
    'email_change_sent' => 'Check your new inbox — we emailed a link to confirm the change. Your email stays the same until you open it.',
    'email_change_cancelled' => 'The email change was cancelled.',
    'email_link_invalid' => 'That email-change link is no longer valid. Please request a new one.',
    'email_taken' => 'That email address is already in use. Your email was not changed.',
    'email_changed' => 'Your sign-in email is now :email. Use that address from now on — the old one no longer works.',
];
