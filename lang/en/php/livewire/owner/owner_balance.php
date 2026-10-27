<?php

// app/Livewire/Owner/OwnerBalance.php — the labels the owner Balance page builds in PHP.

return [
    // the per-row state chip
    'state_available' => 'Available',
    'state_paid_out' => 'Paid out',
    'state_in_transit' => 'In transit',
    'state_on_hold' => 'On hold',
    'state_cleared' => 'Cleared',
    // a month's date span, oldest to newest, e.g. "3 Sep 2026 – 28 Sep 2026"
    'date_range' => ':start – :end',
    // the payout cadence (management agreement)
    'schedule_monthly' => 'Monthly',
    'schedule_quarterly' => 'Quarterly',
    // ledger entry types
    'type_room_revenue' => 'Room revenue',
    'type_commission' => 'Commission',
    'type_management_fee' => 'Management fee',
    'type_ota_cost' => 'OTA cost',
    'type_cleaning' => 'Cleaning',
    'type_addon' => 'Add-on',
    'type_expense' => 'Expense',
    'type_adjustment' => 'Adjustment',
];
