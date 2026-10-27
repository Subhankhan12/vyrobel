<?php

// app/Livewire/Owner/OwnerPayouts.php — the words the owner Payouts page builds in PHP.

return [
    // the status filter pills above the payout history (one per status code present)
    'filter_pending' => 'Pending',
    'filter_held' => 'Held',
    'filter_processing' => 'Processing',
    'filter_paid' => 'Paid',
    'filter_failed' => 'Failed',
    'filter_reversed' => 'Reversed',
    // the real tax-hold banner
    'hold_reason' => 'We’re holding this payout until your tax details are on file.',
    'hold_cta' => 'Add tax details',
    // the payout-account card when the bank account has no name of its own
    'bank_account' => 'Bank account',
    // read-only payout preferences: no minimum payout set
    'minimum_none' => 'None',
    // the payout lifecycle stepper
    'lifecycle_finalized' => 'Finalized',
    'lifecycle_pending' => 'Pending',
    'lifecycle_paid' => 'Paid',
    'note_failed' => 'This payout wasn’t sent. Retrying queues a new payout; the failed attempt stays in your history.',
    'note_reversed' => 'This payout was reversed — the balance carries to your next statement.',
    // the Date column of the payout history for a payout not yet sent
    'date_pending' => 'Pending',
];
