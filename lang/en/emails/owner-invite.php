<?php

// resources/views/emails/owner-invite.blade.php — the owner/team invite email.

return [
    'title' => 'You’re invited to :organization',
    'preheader' => '{1} Set your password to activate your Vyrobel :account — this invitation expires in :count day.|[0,*] Set your password to activate your Vyrobel :account — this invitation expires in :count days.',
    'invited_by' => 'Invited by',
    'greeting' => 'Hi :name, :intro',
    'button' => 'Set your password',
    'paste_link' => 'Or paste this link into your browser:',
    'expires_on_html' => 'This invite is single-use and expires on <strong style="color:#2B2320;">:date</strong>.',
    'ignore_note' => 'If you didn’t expect this, you can safely ignore this email — no account is created until you set a password.',
    'sign_in' => 'Sign in',
    'footer_reason' => 'Vyrobel · hospitality, warmly run · You’re receiving this because :operator :reason',
    'footer_note' => 'Sent automatically when an operator invites someone to their organization.',
];
