<?php

// resources/views/livewire/owner/balance.blade.php — the owner's Balance page (the clearing view of their ledger).

return [
    'title' => 'Balance & withdrawals',
    'subtitle' => 'What’s cleared, what’s held, and where every payment is on its way to.',
    'available' => 'Available',
    // :currency is the ISO code of the headline figure, e.g. EUR
    'available_sub' => 'cleared this period · :currency',
    'on_hold' => 'On hold',
    'on_hold_sub_held' => 'tax details needed',
    'on_hold_sub_none' => 'nothing held',
    'in_transit' => 'In transit',
    'in_transit_sub_moving' => 'on its way',
    'in_transit_sub_none' => 'nothing in transit',
    'carried_forward' => 'Carried forward',
    'carried_forward_body' => 'A refunded or negative statement is carried into your next payout period — it offsets a future payout instead of being charged back, and it’s already included in your Net position.',
    'withdraw_heading' => 'Withdraw early',
    // :schedule is the payout cadence in lower case, e.g. "monthly"
    'withdraw_note_schedule' => 'Anything cleared is paid out automatically on your :schedule schedule. Instant self-serve withdrawal activates with the live payments connection.',
    'withdraw_note' => 'Anything cleared is paid out automatically. Instant self-serve withdrawal activates with the live payments connection.',
    // :iban is the masked account number, e.g. ···1000
    'to_iban' => 'to IBAN :iban',
    'withdraw_disabled_title' => 'Instant withdrawal activates with the live payments connection',
    'withdraw_button' => 'Withdraw',
    // :amount is the available money, already formatted
    'withdraw_amount_button' => 'Withdraw :amount',
    'activity_heading' => 'Balance activity',
    'activity_sub' => 'Grouped by month, newest first — open a month for every movement in it',
    'export_title' => 'Export your balance activity as CSV',
    'col_activity' => 'Activity',
    'col_amount' => 'Amount',
    'col_balance' => 'Balance',
    // the toggle on a month's summary row
    'show_movements' => '{1} Show the :count movement|[0,*] Show the :count movements',
    'hide_movements' => '{1} Hide the :count movement|[0,*] Hide the :count movements',
    'prior_period' => 'Prior-period adjustment',
    'unverified' => 'Unverified',
    'on_a_statement' => 'on a statement',
    'not_on_a_statement' => 'not yet on a statement',
    'empty' => 'No balance activity yet.',
    'multi_currency_note' => 'The running balance accumulates separately for each currency.',
    'by_currency_heading' => 'Balances by currency',
    'col_currency' => 'Currency',
    'col_net_position' => 'Net position',
    'col_paid_to_date' => 'Paid to date',
    'by_currency_note' => 'Each currency is held and totalled separately — balances are never converted.',
    // :amount is the held money, already formatted
    'held_heading' => 'Why is :amount held?',
    'held_body' => 'Your payout can’t be sent until a valid tax ID is on file — a legal requirement before we can transfer rental income.',
    'add_tax_details' => 'Add tax details',
    'schedule_eyebrow' => 'Auto-payout schedule',
    'payouts_link' => 'Payouts →',
    'schedule_note' => 'Everything cleared by the schedule goes out automatically — no action needed.',
    'how_clears_heading' => 'How money clears',
    'how_clears_body' => 'Guest payments clear once the stay begins and the statement is finalised. Refunds and approved expenses are netted before anything reaches your balance.',
];
