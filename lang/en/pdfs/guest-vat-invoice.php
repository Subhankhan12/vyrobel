<?php

// resources/views/pdfs/guest-vat-invoice.blade.php — the guest VAT invoice PDF (labels only; the footer stays English, lawyer list).

return [
    'from' => 'From',
    'registered_legal_name' => 'Registered legal name',
    'vat_reg_no' => 'VAT reg. no. <strong>:vat_id</strong>',
    'billed_to' => 'Billed to',
    'booking_line' => 'Booking :reference · :stay',
    'description' => 'Description',
    'net' => 'Net',
    'no_charges' => 'No charges posted.',
    'vat' => 'VAT',
    'paid' => 'Paid',
    'balance_due' => 'Balance due',
];
