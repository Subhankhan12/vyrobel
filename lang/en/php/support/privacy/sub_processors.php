<?php

// app/Support/Privacy/SubProcessors.php — the console's sub-processor register: who receives personal data because of Vyrobel (I18N-01).
// A row's `name` is the company with what it is used for in brackets; the company names stay as they are. The states are read from the
// configuration (which mailer, whether a provider is switched on) and never carry a value.
return [
    'agreement_none' => 'None on file',

    // the states a row shares
    'state_switched_on' => 'Active — switched on',
    'state_not_active' => 'Not active',
    'state_configured' => 'Configured',

    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Runs the application and its database.',
        'data' => 'Everything the product stores — every account, hotel and guest record.',
        'state' => 'Active — the production host',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Serves and caches the public site in front of the host.',
        'data' => 'Every request passing through it: IP addresses, URLs, cookies in transit.',
        'state' => 'Active — observed on every response',
    ],
    'google' => [
        'name' => 'Google (sign-in)',
        'purpose' => 'Sign in and sign up with a Google account.',
        'data' => 'The person’s Google identity: account id, email, name.',
    ],
    'x' => [
        'name' => 'X (sign-in)',
        'purpose' => 'Sign in and sign up with an X account.',
        'data' => 'The person’s X identity: account id, handle, name.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (email)',
        'purpose' => 'Delivers every transactional email, from the vyrobel.com mailbox.',
        'data' => 'Recipient addresses and message contents — guest names, booking references, dates.',
        'state_active' => 'Active — the mailer',
        // :mailer is the configured mailer's name (log, smtp, …)
        'state_inactive' => 'Not active — mail is “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (payments & billing)',
        'purpose' => 'Subscription billing, guest card payments, owner payouts (Connect).',
        'data' => 'Billing contacts, amounts, booking references; for owner payouts, identity for KYC.',
        'state_fake' => 'Not active — a fake client is bound',
    ],
    'anthropic' => [
        'name' => 'Anthropic (AI assistant)',
        'purpose' => 'Answers questions in the AI assistant.',
        'data' => 'The question asked and the context sent with it.',
        'state_off' => 'Not active — fake client or no key',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Syncs rates, availability and bookings with OTAs.',
        'data' => 'Booking and guest details exchanged with channels.',
        'state_off' => 'Not connected',
    ],
];
