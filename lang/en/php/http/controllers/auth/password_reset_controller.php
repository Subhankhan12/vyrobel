<?php

// app/Http/Controllers/Auth/PasswordResetController.php — forgot / reset password (operator + owner realm).

return [
    // the SAME answer whether or not the address has an account (no enumeration)
    'link_sent' => 'If an account exists for that email, we’ve sent a link to reset your password.',
    'link_invalid' => 'This password reset link is invalid or has expired. Please request a new one.',
];
