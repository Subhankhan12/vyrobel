<?php

// resources/views/components/auth/passkey-remove-dialog.blade.php — the "remove a passkey" dialog on both Security pages.

return [
    // :label is the passkey's name (filled in on the page)
    'title' => 'Remove :label?',
    'sub' => 'That device will need your password to sign in again. Confirm with your password.',
    'your_password' => 'Your password',
];
