<?php

// resources/views/guest/payments.blade.php — the guest's balance and payment history across their stays.

return [
    'title' => 'Payments',
    'card_confirming' => 'A card payment can take a few moments to confirm with your bank. Your balance below updates as soon as it does — there\'s no need to pay again.',
    'badge_balance_due' => 'Balance due',
    'badge_paid_in_full' => 'Paid in full · nothing due',
    'subheading' => 'Your balance and payment history across your stays.',
    'total_charged' => 'Total charged',
    'paid_to_date' => 'Paid to date',
    'tile_balance_due' => 'Balance due',
    'refunded_to_you' => 'Refunded to you',
    'your_bookings' => 'Your bookings',
    'paid' => 'Paid',
    'redirecting' => 'Redirecting…',
    'pay_by_card' => 'Pay by card',
    'no_bookings' => 'No bookings yet.',
    'payment_activity' => 'Payment activity',
    'no_payments' => 'No payments recorded yet.',
    'net_paid' => 'Net paid',
    'payment_methods' => 'Payment methods',
    // the bold part stays inside the sentence so each language places it where its grammar needs it
    'no_card_stored' => 'No card is stored on your account. You enter your card securely on Stripe each time you pay — <strong style="color:var(--vy-ink)">your card details never touch Vyrobel</strong>.',
    'receipts_invoices' => 'Receipts & invoices',
    'receipt' => 'Receipt',
    'receipt_balance_due' => 'balance due',
    'receipt_paid_in_full' => 'paid in full',
    'view' => 'View →',
    'no_receipts' => 'No receipts yet.',
];
