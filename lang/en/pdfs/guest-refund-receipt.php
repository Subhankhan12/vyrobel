<?php

// resources/views/pdfs/guest-refund-receipt.blade.php — the guest refund receipt PDF.

return [
    'eyebrow' => 'Refund receipt',
    'issued' => 'Issued :date',
    'guest' => 'Guest',
    'booking' => 'Booking',
    'col_refund' => 'Refund',
    'col_amount' => 'Amount',
    'refund_to' => 'Refund to :method',
    // the rail the money went back through, as :method above ('Stripe' is a brand and is printed as it is)
    'method_manual' => 'Manual',
    'method_bank_transfer' => 'Bank transfer',
    'total_refunded' => 'Total refunded',
    'footer' => 'A receipt for money returned to you, generated from Vyrobel\'s append-only folio ledger; each refund is recorded as its own reversal and reconciles to your account. Vyrobel · :operator.',
];
