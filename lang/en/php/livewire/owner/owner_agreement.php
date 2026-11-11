<?php

// app/Livewire/Owner/OwnerAgreement.php — the labels the owner Agreement page's class builds (not the contract wording).

return [
    'your_operator' => 'your operator',
    'management_fee' => 'Management fee',
    'rate_of_booking_revenue' => ':rate of booking revenue',
    'payout_schedule' => 'Payout schedule',
    'term' => 'Term',
    // English is the same for every n (it always printed "months" / "days"); other languages add their plural forms
    'months' => ':count months',
    'notice_period' => 'Notice period',
    'days' => ':count days',
    'included_listing' => 'Listing & channel distribution',
    'included_guest_comms' => 'Guest communications & support',
    'included_housekeeping' => 'Housekeeping coordination',
    'included_pricing' => 'Pricing & revenue management',
    'included_statements' => 'Statements & payouts',
    'included_damage' => 'Damage & incident handling',
    'fee_with_interval' => ':fee · :interval',
    'days_either_side' => ':count days, either side',
    'renewal' => 'Renewal',
    'auto_renewing' => 'Auto-renewing',
    'does_not_auto_renew' => 'Does not auto-renew',
    'amendment_label' => ':version amendment',
    'acknowledged_on' => 'Acknowledged :date',
    'awaiting_acknowledgment' => 'Awaiting acknowledgment',
    'portfolio_wide' => 'Portfolio-wide',
    'unit_specific' => 'Unit-specific',
    'interval_monthly' => 'Monthly',
    'interval_quarterly' => 'Quarterly',
    'interval_yearly' => 'Yearly',
    'currency_on_agreement' => 'Set on this agreement',
    'currency_from_profile' => 'From your owner profile',
    'see_agreement_document' => 'See agreement document',
    'starts' => 'Starts :date',
    'ran_to' => 'Ran to :date',
    'status_active' => 'Active',
    'status_ended' => 'Ended',
    // attribution overrides — a stored rule key or value said in words (English = the code's headline, as it always printed);
    // a key or value with no entry here keeps that headline
    'rules' => [
        'non_deductible_categories' => 'Non Deductible Categories',
        'ota_cost_bearer' => 'OTA Cost Bearer',
        'cleaning_fee_bearer' => 'Cleaning fee goes to',
        'addon_bearer' => 'Add-ons go to',
    ],
    'rule_values' => [
        'owner' => 'Owner',
        'operator' => 'Operator',
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'other' => 'Other',
    ],
];
