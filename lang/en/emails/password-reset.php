<?php

// resources/views/emails/password-reset.blade.php — the operator/owner password-reset email.

return [
    'page_title' => 'Reset your Vyrobel password',
    // a plural on the minutes; English prints ":count minutes" for every count, as before
    'preheader' => 'Reset your Vyrobel password — this link is single-use and expires in :count minutes.',
    'eyebrow' => 'Password reset',
    'heading' => 'Reset your Vyrobel password',
    'greeting' => 'Hi :name,',
    'intro' => 'We received a request to reset the password on your Vyrobel account. Choose a new password using the button below. If you didn’t ask for this, you can safely ignore this email — your password won’t change.',
    'reset_button' => 'Reset password',
    'expires_note' => 'This link is single-use and expires in <strong style="color:#4A4038;">:count minutes</strong>.',
    'paste_link' => 'If the button doesn’t work, paste this link into your browser:',
    'footer_no_action' => 'If you didn’t request a password reset, no action is needed.',
    'powered_by' => 'Powered by Vyrobel',
];
