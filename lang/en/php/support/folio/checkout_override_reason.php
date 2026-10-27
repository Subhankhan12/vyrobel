<?php

// app/Support/Folio/CheckoutOverrideReason.php — why a checkout was allowed while money is still owed.

return [
    'rounding_residue' => 'Rounding residue — too small to collect',
    'guest_departed' => 'Guest has already departed',
    'disputed_charge' => 'Charge is disputed and under review',
    'settled_offline' => 'Settled outside the system, not yet recorded',
    'other' => 'Other (note required)',
];
