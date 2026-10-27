<?php

// app/Support/Platform/PlatformFunnel.php — the signup funnel's stage names and the steps it does not measure.

return [
    'stage_signed_up' => 'Signed up',
    'stage_verified' => 'Verified an email (the gate to /app)',
    'stage_first_reservation' => 'First reservation',
    'stage_first_check_in' => 'First check-in',
    'stage_paid' => 'Paid (charged by Stripe)',
    'unmeasured_visited' => 'Visited the site',
    'unmeasured_visited_why' => 'No analytics or visit log is recorded, by design.',
    'unmeasured_started_form' => 'Started the sign-up form',
    'unmeasured_started_form_why' => 'Nothing is written until the form is submitted.',
    'unmeasured_reached_app' => 'Reached /app',
    'unmeasured_reached_app_why' => 'No page view is recorded. “Verified an email” is the nearest recorded step — every /app page requires it.',
];
