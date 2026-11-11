<?php

// app/Http/Controllers/Owner/OwnerAccountController.php — the owner's Profile & account: notices and field names in errors.

return [
    'profile_saved' => 'Your profile is saved.',
    'photo_updated' => 'Your photo is updated.',
    'photo_removed' => 'Your photo is removed.',
    // field names inside the standard validation messages ("The :attribute field is required.")
    'attr_email' => 'email address',
    'attr_current_password' => 'current password',
    // D-MAIL-CLAIMS: while email is not sent from Vyrobel the confirmation link could never arrive
    'email_change_no_mail' => 'Email isn’t being sent from Vyrobel yet, so we can’t send the link that confirms a new address. Nothing was changed.',
    'email_change_sent' => 'Check your new inbox — we emailed a link to confirm the change. Your email stays the same until you click it.',
    'email_change_cancelled' => 'The email change was cancelled.',
    'email_link_invalid' => 'That email-change link is no longer valid. Please request a new one.',
    'email_in_use' => 'That email address is already in use. Your email was not changed.',
    'email_updated' => 'Your email address has been updated. Please sign in with your new address.',
];
