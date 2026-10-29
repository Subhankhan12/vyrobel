<?php

// app/Models/OwnerStatementLine.php — label(): the words for a statement line, by its line_type. Each English value is EXACTLY the
// description OwnerStatementService stores for that line (its LABEL map + "Net payable"), so English reads as stored.

return [
    'gross' => 'Gross room revenue',
    'other_revenue' => 'Other revenue (cleaning / add-ons)',
    'commission' => 'Management commission',
    'management_fee' => 'Management fee',
    'expense' => 'Deductible expenses',
    'ota_cost' => 'OTA commission',
    'payment_fee' => 'Payment & ops fees',
    'withholding' => 'Withholding tax',
    'prior_period_adjustment' => 'Prior-period adjustments',
    'net' => 'Net payable',
];
