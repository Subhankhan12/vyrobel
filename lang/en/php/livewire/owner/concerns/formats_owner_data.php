<?php

// app/Livewire/Owner/Concerns/FormatsOwnerData.php — the shared owner-portal labels (statement and payout status), keyed by their codes.

return [
    // the topbar chip when no user is signed in
    'viewer_fallback' => 'Owner',
    // statement status, keyed by the owner_statements.status code
    'statement_paid' => 'Paid',
    'statement_finalized' => 'Payout pending',
    'statement_draft' => 'Draft',
    // payout status, keyed by the payouts.status code
    'payout_paid' => 'Paid',
    'payout_processing' => 'In transit',
    'payout_held' => 'On hold',
    'payout_failed' => 'Payout failed',
    'payout_reversed' => 'Reversed',
    'payout_pending' => 'Payout pending',
];
