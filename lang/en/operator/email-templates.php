<?php

// resources/views/operator/email-templates.blade.php — the guest email templates manager (the page chrome only).

return [
    'title' => 'Guest emails',
    'subtitle' => 'Customize the emails your guests receive',
    'breadcrumb_label' => 'Breadcrumb',
    'settings_crumb' => 'Settings',
    'preview_emails' => 'Preview emails',
    'intro' => 'These are the transactional emails Vyrobel sends your guests. Edit the wording, turn sections on or off, or pause an email entirely — <strong>your changes take effect on the next real send</strong>. Delivery goes through your email provider in production (account-gated); in dev it’s caught locally.',
    'status_active' => 'Active',
    'status_off' => 'Off',
    'customized' => 'Customized',
    'preview' => 'Preview',
    'toggle_turn_off' => ':template — turn off',
    'toggle_turn_on' => ':template — turn on',
    'footer' => 'Owner & operator alerts email through the same preference gate — see Notification preferences. Open/click tracking isn’t collected, so no engagement stats are shown.',
];
