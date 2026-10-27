<?php

// resources/views/emails/account-deletion.blade.php — the guest account-deletion-scheduled email.

return [
    'title' => 'Your account deletion is scheduled',
    'preheader' => 'You have :count days to change your mind before anything is removed.',
    'eyebrow' => 'Account',
    'greeting' => 'Hi :name, :intro',
    'scheduled_for' => 'Scheduled for',
    'undo_html' => 'Changed your mind? Sign in to your account any time before then and choose <strong>“Undo — keep my account”</strong>. Nothing is removed until the date above; your upcoming reservations are then cancelled per the property\'s policy.',
    'not_you' => 'If you didn\'t request this, please sign in and undo it, or contact the property.',
];
