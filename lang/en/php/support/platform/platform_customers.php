<?php

// app/Support/Platform/PlatformCustomers.php — the console's customer directory, triage buckets and status words.

return [
    'bucket_unverified' => 'Unverified for 7+ days',
    'bucket_unverified_definition' => 'Created more than 7 days ago, and none of its users has confirmed an email address.',
    'bucket_never_signed_in' => 'Never signed in',
    'bucket_never_signed_in_definition' => 'None of its users has ever completed a sign-in. ⚠️ The sign-up session itself is not a sign-in, so this means "never came back after signing up".',
    'bucket_via_x' => 'Created with X',
    'bucket_via_x_definition' => 'Signed up through X. X does not prove an email address the way Google does — the accepted squatting risk. Surfaced for review, never acted on automatically.',
    'bucket_abandoned' => 'Abandoned signups (30+ days)',
    'bucket_abandoned_definition' => 'Created more than 30 days ago, nobody confirmed an email address, and nobody ever signed in. Open one to suspend it, then delete it with your password. Nothing is ever removed automatically.',
    'status_active' => 'Active',
    'status_trialing' => 'On a free trial',
    'status_suspended' => 'Suspended',
    'status_cancelled' => 'Cancelled',
    'status_archived' => 'Archived',
    'subscription_trialing' => 'On a free trial',
    'subscription_active' => 'Active — billed',
    'subscription_past_due' => 'Past due',
    'subscription_canceled' => 'Cancelled',
    'subscription_incomplete' => 'Incomplete',
    'subscription_paused' => 'Paused',
    // the plan name is the database's; the trial note stays beside it
    'plan_on_free_trial' => ':plan · free trial',
    'plan_complimentary' => ':plan · complimentary',
];
