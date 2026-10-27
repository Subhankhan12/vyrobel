<?php

// app/Support/Pdf/OwnerRemittanceDocument.php — the owner remittance advice PDF's amount label, status and fallbacks.

return [
    'owner_fallback' => 'Owner',
    'amount_net_paid' => 'Net paid to owner',
    'amount_net_reversed' => 'Net reversed',
    'amount_net_payable' => 'Net payable to owner',
    // payouts.status, as the status pill reads
    'status_pending' => 'Pending',
    'status_held' => 'Held',
    'status_processing' => 'Processing',
    'status_paid' => 'Paid',
    'status_failed' => 'Failed',
    'status_reversed' => 'Reversed',
];
