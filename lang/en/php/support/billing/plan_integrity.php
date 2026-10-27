<?php

// app/Support/Billing/PlanIntegrity.php — why a plan cannot be offered to new subscribers yet (admin console, Plans).

return [
    'unknown_shape' => 'Its pricing shape is not one the billing engine understands.',
    'per_unit_needs_rate' => 'A per-unit plan needs a unit rate above zero before it can be sold.',
    'flat_needs_price' => 'A flat plan needs a price above zero before it can be sold.',
    'no_price_id' => 'It has no Stripe Price id yet — create the Price in Stripe and paste its id here first.',
    'placeholder_price_id' => 'It is still wired to a placeholder Price id, which does not exist in Stripe.',
];
