<?php

// App\Support\OAuth\OAuthScopes — what each scope lets a connected app read (the consent screen, the owner Security page).

return [
    'read_bookings' => 'Read your bookings — reference, guest name, dates, unit and status (never guest contact details).',
    'read_statements' => 'Read your statements — periods, amounts and status.',
    'read_units' => 'Read your units — code, name, type and occupancy (never door codes or Wi-Fi).',
];
