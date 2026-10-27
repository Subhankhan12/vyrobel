<?php

// resources/views/emails/login-approval.blade.php — the login-approval email.

return [
    'title' => 'Approve your Vyrobel sign-in',
    'preheader' => 'Approve the sign-in from :device — this link expires in 30 minutes.',
    'eyebrow' => 'Approve sign-in',
    'heading' => 'Is this you?',
    'body' => 'Hi :name, someone just signed in to your Vyrobel account from a new device and it’s waiting for your approval:',
    'button' => 'Approve this sign-in',
    'expiry_html' => 'This link expires in 30 minutes and can be used once. <strong style="color:#8A2F27;">If this wasn’t you, don’t click it</strong> — change your password instead.',
];
