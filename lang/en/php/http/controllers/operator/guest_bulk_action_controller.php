<?php

// app/Http/Controllers/Operator/GuestBulkActionController.php — the Guests list's bulk actions (message, tag, erase, export).

return [
    'email_not_sent' => 'Email isn’t being sent from Vyrobel yet — nothing was sent.',
    'guests_messaged' => '{1} :count guest messaged|[0,*] :count guests messaged',
    'skipped_no_consent' => ':count skipped (no marketing consent)',
    'skipped_no_email' => ':count skipped (no email)',
    // the parts above, joined with " · "
    'message_summary' => ':parts.',
    'guests_tagged' => '{1} :count guest tagged “:tag”.|[0,*] :count guests tagged “:tag”.',
    'confirm_exact' => 'Type ERASE exactly to confirm this permanent erasure.',
    'confirm_required' => 'Type ERASE to confirm this permanent erasure.',
    'guests_erased' => '{1} :count guest erased. Their personal data is gone; the financial history remains.|[0,*] :count guests erased. Their personal data is gone; the financial history remains.',
    'csv_name' => 'Name',
    'csv_email' => 'Email',
    'csv_phone' => 'Phone',
    'csv_city' => 'City',
    'csv_nationality' => 'Nationality',
    'csv_stays' => 'Stays',
    'csv_vip' => 'VIP',
    'csv_marketing_consent' => 'Marketing consent',
    'anonymized_guest' => 'Anonymized guest',
    'unnamed_guest' => 'Unnamed guest',
    'workspace_paused' => 'Your workspace is paused (read-only) — bulk actions are off until you resume. Resume from Billing.',
];
