<?php

// app/Support/Pdf/FolioDocument.php — the folio / statement-of-account PDF's ledger lines (labels, notes, flags).

return [
    'desc_received' => 'Received',
    'desc_reverses_payment' => 'Reverses a payment',
    'desc_pre_authorized' => 'Pre-authorized',
    'flag_hold' => 'Hold',
    'desc_not_counted' => 'Not counted in the balance',
    // folio item types (folio_items.type)
    'item_room' => 'Room',
    'item_tax' => 'Tax',
    'item_fee' => 'Fee',
    'item_add_on' => 'Add on',
    'item_product' => 'Product',
    'item_manual' => 'Manual',
    'item_discount' => 'Discount',
    // payment types (payments.type)
    'payment_payment' => 'Payment',
    'payment_capture' => 'Capture',
    'payment_refund' => 'Refund',
    'payment_deposit_hold' => 'Deposit hold',
    'payment_void' => 'Void',
    'payment_chargeback' => 'Chargeback',
    // payment statuses, as the flag on a line that is not counted (payments.status)
    'flag_pending' => 'Pending',
    'flag_succeeded' => 'Succeeded',
    'flag_failed' => 'Failed',
    'flag_refunded' => 'Refunded',
    'flag_authorized' => 'Authorized',
    'flag_captured' => 'Captured',
    'flag_voided' => 'Voided',
];
