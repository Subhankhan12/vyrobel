<?php

// app/Livewire/Owner/OwnerSettings.php — the labels the owner Settings hub builds in PHP.

return [
    // the notification channels (live, then not built yet)
    'notif_in_app' => 'In-app',
    'notif_email' => 'Email',
    'notif_sms' => 'SMS',
    'notif_push' => 'Push',
    // connected channels (the OTA brand names stay as they are)
    'channel_direct' => 'Direct · Vyrobel',
    'channel_direct_desc' => 'Your Vyrobel booking page & widget',
    'channel_ota_desc' => 'Sync listings, rates & bookings',
    // data export rows
    'export_bookings' => 'Bookings (CSV)',
    'export_statement_history' => 'Statement history (CSV)',
    'export_full_account' => 'Full account data (JSON)',
    // language & region rows
    'region_currency' => 'Currency',
    'region_timezone' => 'Timezone',
    // the team summary: the primary owner's role
    'role_account_owner' => 'Account owner',
    // the profile-completeness checklist
    'check_profile_photo' => 'Add a profile photo',
    'check_login_email' => 'Confirm your login email',
    'check_tax_details' => 'Add your tax details',
    'check_payout_account' => 'Add a payout account',
    'check_two_factor' => 'Turn on two-factor sign-in',
];
