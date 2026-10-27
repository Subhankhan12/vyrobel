<?php

// resources/views/guest/wallet.blade.php — the guest's Wallet: available credit, redeem a code, apply to a booking, history.

return [
    'page_title' => 'Wallet',
    'heading' => 'Wallet',
    'available_credit' => 'Available credit',
    'available_note' => 'Across all your Vyrobel stays · applied at checkout. Expired credit isn’t included.',
    'redeem_heading' => 'Redeem a code',
    'code_placeholder' => 'Enter a code',
    'code_label' => 'Promo or gift code',
    'redeem_button' => 'Redeem',
    'apply_heading' => 'Apply to a booking',
    // after the booking reference: "VYR-1234 · €120.00 owed"
    'owed' => ':amount owed',
    'apply_amount_label' => 'Credit to apply to booking :reference',
    'apply_button' => 'Apply',
    'history_heading' => 'Every credit added & used',
    // tags after a ledger line: "Promo code · expired", "SUMMER10 · expires 30 Apr 2027"
    'expired' => 'expired',
    'expires_on' => 'expires :date',
    'history_empty' => 'No credit yet — redeem a code to add some.',
    'how_heading' => 'How credits work',
    'how_source' => 'Credit comes from any code you redeem, plus any credit added to your account.',
    'how_applied' => 'It’s applied to your bookings at checkout, or to an outstanding balance above.',
    'how_expiry' => 'Some credit expires — when it does, the date shows on that entry. Credit with no date doesn’t expire.',
    'loyalty_soon' => 'Loyalty tiers and a refer-a-friend programme are coming soon.',
];
