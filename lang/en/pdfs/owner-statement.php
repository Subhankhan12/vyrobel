<?php

// resources/views/pdfs/owner-statement.blade.php — the owner monthly statement PDF (letterhead).

return [
    'managed_with_vyrobel' => 'managed with Vyrobel',
    'eyebrow' => 'Owner statement',
    'unit_count' => '{1} :count unit|[0,*] :count units',
    'issued' => 'issued :date',
    'prepared_for' => 'Prepared for',
    'net_payable_to_owner' => 'Net payable to owner',
    'iban' => 'IBAN :iban',
    'occupancy' => 'Occupancy',
    'nights_sold' => 'Nights sold',
    'avg_nightly_rate' => 'Avg. nightly rate',
    'breakdown' => 'Breakdown',
    'item' => 'Item',
    'detail' => 'Detail',
    'amount' => 'Amount',
    'provisional' => '(provisional)',
    'performance_by_unit' => 'Performance by unit',
    'unit' => 'Unit',
    'nights' => 'Nights',
    'revenue' => 'Revenue',
    'total_gross_room_revenue' => 'Total gross room revenue',
    'callout_heading' => 'Payout.',
    'settles_to_iban' => 'The net amount of :amount settles by bank transfer to IBAN :iban.',
    'settles' => 'The net amount of :amount settles by bank transfer.',
    // with no commission rate the clause opens the sentence in lower case, exactly as the English always read
    'fees_with_commission' => 'Management commission is charged at :pct% of gross room revenue; payment and platform fees are passed through at cost. All amounts are in :currency and include applicable taxes collected on your behalf.',
    'fees' => 'payment and platform fees are passed through at cost. All amounts are in :currency and include applicable taxes collected on your behalf.',
    'fx_note' => 'Converted from :source at :rate (source: :src).',
    'provisional_note' => 'Some lines are provisional, pending channel reconciliation.',
    'statement_ref' => 'statement :ref',
    'sequence_no' => 'no. :number',
    'generated' => 'generated <span style="white-space:nowrap;">:date</span>',
    'questions' => 'Questions? :email',
];
