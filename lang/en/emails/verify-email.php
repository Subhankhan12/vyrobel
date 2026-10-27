<?php

// resources/views/emails/verify-email.blade.php — the guest email verification (heading/intro/button come from the template).

return [
    'page_title' => 'Confirm your email address',
    'preheader' => 'Confirm your email to activate your account.',
    'eyebrow' => 'Confirm to activate',
    'expires' => 'This link expires in :duration.',
    'copy_link' => 'Button not working? Copy and paste this link into your browser:',
    'not_requested' => 'Didn’t request this? You can safely ignore this email — nothing will be activated and no account is created until it’s verified.',
];
