<?php

// app/Http/Controllers/Guest/GuestWalletController.php — the guest's wallet (credit ledger, redeem, apply to a booking).

return [
    'overdraw' => 'That’s more than you can apply — check your balance and the amount owed.',
    'credit_applied' => 'Credit applied to your booking.',
    // what a wallet entry is
    'type_refund' => 'Refund credit',
    'type_add_on_cancelled' => 'Add-on cancelled',
    'type_referral' => 'Referral bonus',
    'type_loyalty' => 'Loyalty reward',
    'type_birthday' => 'Birthday credit',
    'type_promo' => 'Promo code',
    'type_applied' => 'Applied to booking',
    'type_reversal' => 'Reversed',
    'type_adjustment' => 'Adjustment',
];
