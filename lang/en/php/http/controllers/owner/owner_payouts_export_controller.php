<?php

// app/Http/Controllers/Owner/OwnerPayoutsExportController.php — the owner payout-history CSV: column headings and cell words.

return [
    'col_month' => 'Month',
    'col_rail' => 'Rail',
    'col_date' => 'Date',
    'col_status' => 'Status',
    'col_amount' => 'Amount',
    // the Date cell of a payout not yet sent
    'date_pending' => 'Pending',
    'status_pending' => 'Pending',
    'status_processing' => 'Processing',
    'status_paid' => 'Paid',
    'status_failed' => 'Failed',
    'status_reversed' => 'Reversed',
    'status_held' => 'Held',
];
