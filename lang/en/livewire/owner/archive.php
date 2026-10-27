<?php

// resources/views/livewire/owner/archive.blade.php — the owner's Statements archive (finalized statement history).

return [
    'title' => 'Statements archive',
    'history_summary' => 'Your finalized history · :years · :statements',
    'years' => '{1} :count year|[0,*] :count years',
    'statements' => '{1} :count statement|[0,*] :count statements',
    'archived_on_finalize' => 'Statements are archived here the moment they are finalized.',
    'net_earned_lifetime' => 'Net earned · lifetime',
    'since' => 'since :date',
    'per_currency_note' => 'Totalled per payout currency — balances in different currencies are never combined.',
    'lifetime_empty' => 'No finalized statements yet — your first one will appear here.',
    'search_placeholder' => 'Search your statements — month, year, status or number',
    'search_label' => 'Search statements',
    // :query is the live search box text (an Alpine x-text span), kept inside the sentence
    'no_match' => 'No statements match “:query”.',
    'no_match_hint' => 'Try a month (“September”), a year (“2026”), a status (“Paid”) or a statement number (“No. 1”).',
    'group_summary' => ':statements · net :nets',
    'per_payout_currency' => '(per payout currency)',
    'tax_summary_title' => 'Download the :year annual tax summary as a PDF',
    'tax_summary' => 'Tax summary',
    'col_statement' => 'Statement',
    'col_net_payable' => 'Net payable',
    'col_links' => 'Links',
    'unverified_title' => 'Some lines are still pending channel reconciliation',
    'unverified' => 'Contains unverified lines',
    'pdf_title' => 'Download this statement as a PDF',
    'pdf' => 'PDF',
    'bookings_title' => 'Download the bookings behind this statement as a PDF',
    'bookings' => 'Bookings',
    'empty' => 'No finalized statements yet. A statement is archived here once it is finalized.',
    'zip_heading' => 'Download the full archive',
    'zip_sub' => '{1} One ZIP of :count statement PDF. Each statement above already downloads on its own.|[0,*] One ZIP of :count statement PDFs. Each statement above already downloads on its own.',
    'download_zip' => 'Download ZIP',
];
