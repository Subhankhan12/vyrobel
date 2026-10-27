<?php

// resources/views/pdfs/guest-credit-note.blade.php — the guest credit note PDF.

return [
    'eyebrow' => 'Credit note',
    'issued' => 'Issued :date',
    'from' => 'From',
    'credited_to' => 'Credited to',
    'booking_line' => 'Booking :reference · :stay',
    'col_credit' => 'Credit',
    'col_amount' => 'Amount',
    'credit_against_invoice' => 'Credit against invoice',
    'total_credited' => 'Total credited',
    'footer' => 'A credit note for your booking, generated from Vyrobel\'s append-only folio ledger; it records a credit against your invoice matching the refund issued, and reconciles to your account. Vyrobel · :operator.',
];
