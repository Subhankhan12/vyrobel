<?php

// app/Exceptions/Payments/OperatorNotReadyForPaymentsException.php — the refusal when a property's Stripe account cannot take cards yet.

return [
    'message' => 'This property cannot take card payments yet: its Stripe account has not finished verification. Someone who manages billing for the account can finish it under Billing → Card acceptance. Until then, take payment another way and record it on the folio.',
];
