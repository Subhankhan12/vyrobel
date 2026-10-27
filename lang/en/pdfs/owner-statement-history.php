<?php

// resources/views/pdfs/owner-statement-history.blade.php — the owner statement-history PDF.

return [
    'managed_with_vyrobel' => 'managed with Vyrobel',
    'eyebrow' => 'Statement history',
    'statement_count' => '{1} :count statement|[0,*] :count statements',
    'month' => 'Month',
    'gross' => 'Gross',
    'net_payable' => 'Net payable',
    'status' => 'Status',
    'empty_state' => 'No finalized statements in this period.',
    'total_net_payable' => 'Total net payable',
    'footer' => 'Generated from Vyrobel\'s owner ledger; each row reconciles to that month\'s owner statement.',
];
