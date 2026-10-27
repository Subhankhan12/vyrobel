<?php

// app/Support/Owner/PayoutRecovery.php — what the owner must fix before a failed payout can be tried again.

return [
    'no_account_title' => 'Connect your payout account',
    'no_account_detail' => 'We have no connected account to send this payout to, so another attempt would fail the same way.',
    'no_account_cta' => 'Set up your payout account',
    'not_enabled_title' => 'Your payout account is not ready yet',
    'not_enabled_detail' => 'Your account is connected, but payouts have not been enabled on it yet. Finish the remaining details and we can try again.',
    'not_enabled_cta' => 'Review your payout account',
];
