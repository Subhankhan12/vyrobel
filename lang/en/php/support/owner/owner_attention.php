<?php

// App\Support\Owner\OwnerAttention — the owner's "Needs your attention" feed (My Units, Overview) and its Snoozed panel.

return [
    // a snoozed item comes back "today" (otherwise Carbon's "in 3 days")
    'until_today' => 'today',
    'tax_hold_title' => 'Add tax details to release payout',
    'tax_hold_body' => 'Your payout is on hold until your tax details are on file.',
    'tax_hold_cta' => 'Add tax details',
    'payout_method_title' => 'Confirm your payout bank account',
    'payout_method_body' => 'Add a payout method so we can send your earnings.',
    'payout_method_cta' => 'Payout method',
    'renewal_title' => 'Management agreement renews soon',
    'renewal_body' => '{1} Your agreement ends in :count day — review the terms.|[0,*] Your agreement ends in :count days — review the terms.',
    'renewal_cta' => 'Review agreement',
    'proposal_title' => 'Rate change to approve',
    'proposal_cta' => 'Open Rates',
    // one English word, two acts: accepting a proposed rate and releasing an expense read differently in other languages
    'approve_rate' => 'Approve',
    'approve_expense' => 'Approve',
    'decline' => 'Decline',
    'expense_title' => 'Expense awaiting your approval',
    // :description is the expense as the operator wrote it; :amount is formatted money
    'expense_body' => ':description — :amount (charged to your payout once approved)',
    'expense_cta' => 'Open Expenses',
    'query' => 'Query',
    'room_type_fallback' => 'Room type',
    'no_rate_set' => 'no rate set',
    // a weekend uplift, e.g. "+15% weekends"
    'extra_weekends' => '+:pct% weekends',
    // a minimum stay in nights, short: "min stay 3n"
    'extra_min_stay' => 'min stay :nightsn',
    // e.g. "Deluxe Double: €120.00 → €135.00 / night"
    'proposal_summary' => ':room_type: :from → :to / night',
    // the same, with the extras in brackets: "… / night (+15% weekends · min stay 3n)"
    'proposal_summary_extra' => ':room_type: :from → :to / night (:extra)',
];
