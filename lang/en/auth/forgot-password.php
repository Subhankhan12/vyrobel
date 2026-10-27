<?php

// resources/views/auth/forgot-password.blade.php — ask for a password-reset link.

return [
    'page_title' => 'Reset password · Vyrobel',
    'heading' => 'Reset your password',
    // :count is the link's life in minutes, read from config (auth.passwords.users.expire)
    'intro' => 'Enter the email on your account and we’ll send you a link to choose a new password. The link is valid for :count minutes.',
    'send_reset_link' => 'Send reset link',
    'back_to_sign_in' => '← Back to sign in',
];
