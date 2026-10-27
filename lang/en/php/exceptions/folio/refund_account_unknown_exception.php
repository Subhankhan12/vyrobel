<?php

// app/Exceptions/Folio/RefundAccountUnknownException.php — the refusal of an automatic refund on a payment with no recorded account.

return [
    'message' => 'This payment was taken through Stripe but does not record which account received it, so it cannot be refunded automatically. Someone who manages billing for the account can refund it with Stripe directly; record the refund on the folio once it is done.',
];
