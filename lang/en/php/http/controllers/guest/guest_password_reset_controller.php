<?php

// app/Http/Controllers/Guest/GuestPasswordResetController.php — forgot / reset password on the guest realm.

return [
    // the SAME answer whether or not the address has an account (no enumeration)
    'link_sent' => 'If an account exists for that email, we’ve sent a link to reset your password.',
    'link_invalid' => 'This password reset link is invalid or has expired. Please request a new one.',
    'password_reset' => 'Your password has been reset — you can now sign in.',
];
