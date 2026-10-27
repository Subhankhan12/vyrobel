<?php

// app/Http/Controllers/ReservationFolioActionController.php — folio payments, charges, refunds and billing (flash messages).

return [
    'card_not_live' => 'Card payments go through Stripe (test-mode) — available at the live-key flip. Record cash or a bank transfer to take an on-desk payment now.',
    'payment_amount_invalid' => 'Enter a payment amount greater than zero.',
    'payment_recorded' => 'Payment recorded.',
    'charge_amount_invalid' => 'Enter a charge amount greater than zero.',
    'charge_added' => 'Charge added.',
    'refund_only_settled' => 'Only a settled payment can be refunded.',
    'already_fully_refunded' => 'That payment is already fully refunded.',
    'refund_amount_invalid' => 'Enter a refund amount greater than zero.',
    'refund_exceeds' => 'That refund was refused — only :amount :currency is still refundable against that payment.',
    'refund_issued' => 'Refund issued.',
    'billed_to_account' => 'Billed to :account. The balance stays owed until they pay.',
    'returned_to_guest' => 'Returned to the guest’s folio.',
];
