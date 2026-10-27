<?php

// resources/views/livewire/owner/statement.blade.php — the owner's single Statement page (breakdown, payout, history, by-unit split).

return [
    'title' => 'Statement',
    // :period is the month, e.g. "September 2026"
    'previous_period' => 'Previous period, :period',
    'jump_to_another_month' => 'Jump to another month',
    'jump_to_month' => 'Jump to month',
    'next_period' => 'Next period, :period',
    // under the period eyebrow: the number of the owner's units
    'units' => '{1} :count unit|[0,*] :count units',
    'net_payable' => 'Net payable',
    // :currency is an ISO code, :rate the exchange rate, :source who published it
    'converted_from' => 'Converted from :currency at :rate',
    'converted_from_source' => 'Converted from :currency at :rate · :source',
    'provisional' => 'Provisional',
    'pending_reconciliation' => 'Some lines are pending channel reconciliation.',
    'breakdown' => 'Breakdown',
    'next_payout' => 'Next payout',
    // :date is the day the payout was paid, :rail how it was paid (e.g. "Bank transfer")
    'paid_on' => 'Paid :date',
    'paid_on_via' => 'Paid :date · :rail',
    'held_for_tax' => 'Held until your tax details are on file.',
    'bank_transfer' => 'Bank transfer',
    'occupancy' => 'Occupancy',
    // :summary lists each month and its occupancy, e.g. "Mar 40%, Apr 55%"
    'monthly_occupancy' => 'Monthly occupancy: :summary',
    'download_statement_title' => 'Download this statement as a PDF',
    'download_statement' => 'Download statement',
    'more_documents' => 'More documents',
    'remittance_title' => 'Download the remittance advice for this payout as a PDF',
    'remittance_advice' => 'Remittance advice',
    // RA = remittance advice; :period is the month
    'remittance_sub' => 'RA · :period · PDF',
    'remittance_pending' => 'Available once your payout is issued',
    'booking_summary_title' => 'Download the bookings behind this statement as a PDF',
    'booking_summary' => 'Booking summary',
    'booking_summary_sub' => 'The bookings behind this statement · PDF',
    'statement_history' => 'Statement history',
    // :total is the net total, already formatted (one figure per currency)
    'history_summary' => '{1} :count statement · :total net total|[0,*] :count statements · :total net total',
    'export_csv_title' => 'Export the statement history as CSV',
    'export_csv' => 'Export CSV',
    'export_pdf_title' => 'Export the statement history as PDF',
    'export_pdf' => 'Export PDF',
    'col_month' => 'Month',
    'col_gross' => 'Gross',
    // :month is the short month, e.g. "Sep 2026"
    'download_month_statement' => 'Download :month statement',
    'by_unit' => 'By unit',
    // :period is the month
    'by_unit_sub' => '{1} :period · :count unit|[0,*] :period · :count units',
    // :pct is the unit's occupancy percentage
    'unit_nights_occupancy' => '{1} :count night · :pct% occupancy|[0,*] :count nights · :pct% occupancy',
    // :gross is the unit's gross, already formatted; :pct its share of the net
    'unit_gross_share' => ':gross gross · :pct% of net',
    'whole_portfolio' => 'Whole portfolio',
    'not_attributed' => 'Not attributed to a single unit',
    'pct_of_net' => ':pct% of net',
    'total_net_earnings' => 'Total net earnings',
    'available_once_finalized' => 'Available once finalized',
    'by_unit_deferred' => 'A per-unit split of this statement (net · gross · occupancy) appears here once the statement is finalized — it is derived from the exact ledger lines the statement claims.',
];
