<?php

// app/Support/Billing/UniquePlanTierCycle.php — the validation message when a plan code collides with another plan's tier.

return [
    // :tier and :plan are plan codes; :interval is the billing interval code (monthly / yearly)
    // the interval word printed in :interval — lower case, as it sits in the sentence
    'interval_monthly' => 'monthly',
    'interval_yearly' => 'yearly',
    'tier_taken' => 'This code resolves to the tier [:tier], which plan [:plan] already occupies for the :interval interval. Two plans in one tier and cycle cannot both be shown on the pricing page — one would silently disappear. Choose a different tier name.',
];
