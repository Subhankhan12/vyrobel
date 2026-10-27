<?php

// resources/views/emails/refund-confirmation.blade.php — the guest refund confirmation (heading/intro/button come from the template).

return [
    'page_title' => 'Your refund has been processed',
    'preheader' => 'We’ve refunded :amount to your original payment method.',
    'eyebrow' => 'Refunded',
    'amount_refunded' => 'Amount refunded',
    'to_method' => 'To :method',
    'reason' => 'Reason',
    'reference' => 'Reference',
    'expected_arrival' => 'Expected arrival',
    'refund_status' => 'Refund status',
    'status_requested' => 'Requested',
    'status_processed' => 'Processed',
    'status_arriving' => 'Arriving in your account',
    'closing' => 'Refunds are returned to your original payment method. Your bank may take a little longer to post it to your account. Questions? Just <a href=":url" style="color:#C2410C; text-decoration:underline;">message us</a>.',
];
