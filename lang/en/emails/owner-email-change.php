<?php

// resources/views/emails/owner-email-change.blade.php — the owner's "confirm your new login email" mail.

return [
    'title' => 'Confirm your new email address',
    'preheader' => 'Confirm this address to finish changing your Vyrobel login email.',
    'eyebrow' => 'Email change',
    'heading' => 'Confirm your new email',
    'body_html' => 'Hi :name, you asked to change your Vyrobel login email to <strong>:email</strong>. Confirm below to make the switch — your current email stays active until you do.',
    'button' => 'Confirm new email',
    'expiry' => 'This link expires in an hour and can be used once. If you didn’t request this, you can ignore it — nothing changes.',
    'paste_link' => 'If the button doesn’t work, paste this link into your browser:',
];
