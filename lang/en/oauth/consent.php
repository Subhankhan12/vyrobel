<?php

// resources/views/oauth/consent.blade.php — a third-party app asks for access to an owner's account.

return [
    'page_title' => 'Authorize :app · Vyrobel',
    'heading' => '<span class="app">:app</span> wants to access your account',
    'intro' => 'Authorizing gives this app <strong>read-only</strong> access to only the data listed below — and only your own. You can revoke it anytime from Security → Connected apps.',
    'note' => 'Vyrobel never shares your guests’ contact details, door codes or Wi-Fi. This app can read only what’s listed — nothing more, and never another owner’s data.',
    'deny' => 'Deny',
    'authorize' => 'Authorize',
    'signed_in_as' => 'Signed in as :name',
];
