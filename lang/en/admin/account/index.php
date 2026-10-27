<?php

// resources/views/admin/account/index.blade.php — the platform admin's own account page.

return [
    'page_title' => 'Your account',
    'page_subtitle' => 'Manage your platform-admin profile and sign-in credentials.',
    'new_recovery_codes' => 'Your new recovery codes',
    'new_recovery_codes_help' => 'Save these somewhere safe — each works once, and they replace any previous set. Shown only now.',
    'profile' => 'Profile',
    'save_profile' => 'Save profile',
    'login_email' => 'Login email',
    'current' => 'Current',
    'pending' => 'Pending',
    'awaiting_confirmation' => '· awaiting confirmation',
    'pending_email_help' => 'We emailed a confirmation link to your new address. Your email stays the same until you open it.',
    'cancel_pending_change' => 'Cancel pending change',
    'new_email' => 'New email',
    'current_password' => 'Current password',
    'change_email' => 'Change email',
    'change_email_help' => '🔒 We\'ll email a confirmation link to the new address — your email only changes once you open it.',
    'change_password' => 'Change password',
    'new_password' => 'New password',
    'confirm_new_password' => 'Confirm new password',
    'update_password' => 'Update password',
    // :hint is the password policy's own sentence
    'password_help' => '🔒 :hint Your current password is required.',
    'two_factor' => 'Two-factor authentication',
    'two_factor_active' => 'Active',
    'two_factor_not_set' => 'Not set',
    'two_factor_help' => 'Two-factor is required for platform access. You can regenerate your one-time recovery codes below.',
    'regenerate_recovery_codes' => 'Regenerate recovery codes',
];
