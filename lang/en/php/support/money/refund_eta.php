<?php

// App\Support\Money\RefundEta — how long a refund usually takes to land (guest emails and pages).

return [
    // a RANGE, never a promise; the same figure as RefundEta::WINDOW
    'window' => '5–10 business days',
    'sentence' => 'Refunds usually reach your account within :window, depending on your bank.',
];
