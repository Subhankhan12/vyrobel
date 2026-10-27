<?php

// resources/views/emails/guest-password-reset.blade.php — the guest password-reset email.

return [
    'title' => 'Reset your password',
    'preheader' => 'Reset the password for your Vyrobel guest account.',
    'eyebrow' => 'Password reset',
    'body' => 'Hi :name, we received a request to reset the password for your Vyrobel guest account. Click below to choose a new one — the link expires in an hour.',
    'button' => 'Choose a new password',
    'not_you' => 'If you didn’t request this, you can safely ignore this email — your password won’t change.',
];
