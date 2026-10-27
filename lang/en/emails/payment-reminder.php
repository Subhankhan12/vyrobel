<?php

// resources/views/emails/payment-reminder.blade.php — the guest payment reminder (heading/intro/button come from the template).

return [
    'page_title' => 'A balance is due before your stay',
    'preheader' => 'A balance of :balance is due before your stay at :unit.',
    'eyebrow' => 'Before you arrive',
    'total_stay' => 'Total stay',
    'paid_so_far' => 'Paid so far',
    'balance_due' => 'Balance due',
    'payable_at_checkin' => 'Otherwise payable at check-in',
    'secure_payment' => 'Secure payment · Visa & Mastercard · receipt by email',
    'arriving' => 'Arriving',
    'unit' => 'Unit',
    'reference' => 'Reference',
    'no_rush' => 'No rush — if you’d rather pay on arrival, that’s completely fine. Card and cash are both welcome at the desk. Questions about your balance? Just <a href=":url" style="color:#C2410C; text-decoration:underline;">message us</a>.',
];
