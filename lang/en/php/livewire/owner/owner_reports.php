<?php

// app/Livewire/Owner/OwnerReports.php — the texts the owner Reports component builds (hero, channels, payout, documents, exports, ranges).

return [
    // dark hero
    'hero_eyebrow' => ':period so far',
    'hero_nights_units' => ':nights · :units',
    'hero_nights' => '{1} :count night|[0,*] :count nights',
    'hero_units' => '{1} :count unit|[0,*] :count units',
    // booking sources: the bucket for ledger rows with no reservation
    'channel_other' => 'Other',
    // gross to net
    'commission' => 'Commission',
    'commission_rate' => 'Commission · :percent%',
    // latest payout status
    'payout_pending' => 'Pending',
    'payout_processing' => 'Processing',
    'payout_held' => 'Held',
    'payout_paid' => 'Paid',
    'payout_failed' => 'Failed',
    'payout_reversed' => 'Reversed',
    // documents card
    'doc_statement' => ':month statement',
    'doc_statement_sub' => 'PDF · net & payout',
    // the Export menu
    'export_pdf' => 'PDF report',
    'export_pdf_sub' => 'Formatted earnings summary',
    'export_csv' => 'CSV',
    'export_csv_sub' => 'Monthly & per-unit rows',
    'export_xls' => 'Spreadsheet',
    'export_xls_sub' => 'Opens the CSV in Excel/Sheets',
    'export_tax' => 'Annual tax summary',
    'export_tax_sub' => ':year · for your accountant',
    'export_all' => 'All statements',
    'export_all_sub' => 'Open your Documents',
    'export_pack' => 'PDF pack',
    'export_pack_sub' => 'Bundled statements',
    'export_email' => 'Email to me',
    'export_email_sub' => 'Send my latest statement to my inbox',
    // reporting ranges
    'range_ytd' => 'Year to date',
    'range_6m' => 'Last 6 months',
    // reporting-year menu
    'year_no_statements' => 'No statements yet',
    'year_statements' => '{1} :count statement|[0,*] :count statements',
    'year_statements_ytd' => '{1} :count statement YTD|[0,*] :count statements YTD',
];
