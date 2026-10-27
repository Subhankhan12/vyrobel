<?php

// resources/views/emails/admin-email-change.blade.php — the platform admin's "confirm your new email" mail.

return [
    'title' => 'Confirm your new Vyrobel platform email',
    'preheader' => 'Confirm this address to finish changing your Vyrobel platform-admin login email.',
    'eyebrow' => 'Vyrobel · Platform admin',
    'heading' => 'Confirm your new email',
    'greeting' => 'Hi :name,',
    'body_html' => 'You asked to change your platform-admin login email to <strong style="color:#2B2320;">:email</strong>. Confirm it by opening the link below — your email stays the same until you do. The link expires in one hour.',
    'button' => 'Confirm new email',
    'not_you' => 'If you didn\'t request this, you can ignore this email — nothing changes without opening the link.',
];
