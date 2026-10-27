<?php

// app/Http/Controllers/Operator/BillingActionController.php — the operator's billing actions (details, checkout, pause, cancel, resume).

return [
    'details_saved' => 'Billing details saved.',
    'checkout_failed' => 'We couldn’t start checkout right now — please try again, or contact support.',
    'paused_until' => 'Your workspace is paused (read-only) until :date. Resume any time.',
    'cancel_scheduled' => 'Your subscription is set to cancel. You keep full access until :date, then the workspace is read-only.',
    'resumed' => 'Welcome back — your workspace is live again.',
    // local and test environments only
    'dev_trial_expired' => 'Test only: your trial was expired.',
];
