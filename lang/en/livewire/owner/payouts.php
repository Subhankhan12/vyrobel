<?php

// resources/views/livewire/owner/payouts.blade.php — the owner Payouts page (history, KPIs, next payout, account, schedule, preferences).

return [
    'title' => 'Payouts',
    // the real tax-hold banner
    'hold_title' => 'Your :amount payout is on hold',
    // the failed-payout alert; :period is a month, e.g. "September 2026"
    'failed_title' => 'Your :amount payout couldn’t be sent',
    'failed_title_period' => 'Your :amount :period payout couldn’t be sent',
    'failed_default_reason' => 'The transfer didn’t go through. Nothing was taken from your balance — the amount is still yours.',
    'attempted_on' => '(attempted :date)',
    'already_queued' => 'A replacement payout for this statement is already on its way, so there’s nothing for you to do.',
    'retry_paused' => 'Your payouts are paused in your payout preferences below — resume them to try this payout again.',
    'retry_explainer' => 'Retrying queues a new payout for this statement — your manager sends it on the next payout run.',
    'retry_button' => 'Retry payout',
    // KPI tiles
    'kpi_paid_this_year' => 'Paid this year',
    'payout_count' => '{1} :count payout|[0,*] :count payouts',
    'next_payout' => 'Next payout',
    'awaiting_disbursement' => 'Awaiting disbursement',
    'nothing_pending' => 'Nothing pending',
    'kpi_pending' => 'Pending',
    'on_hold' => 'On hold',
    // payout history
    'history_title' => 'Payout history',
    'history_summary' => '{1} :paid paid across :count payout|[0,*] :paid paid across :count payouts',
    'history_summary_pending' => '{1} :paid paid across :count payout · :pending pending|[0,*] :paid paid across :count payouts · :pending pending',
    'search_placeholder' => 'Search month or status',
    'search_label' => 'Search payouts',
    'export_title' => 'Export your payout history as CSV',
    'col_month' => 'Month',
    'col_rail' => 'Rail',
    'col_amount' => 'Amount',
    'col_links' => 'Links',
    'statement_link' => 'Statement',
    'remittance_title' => 'Download the remittance advice for this payout as a PDF',
    'remittance_link' => 'Remittance',
    'history_empty' => 'No payouts yet.',
    // the next-payout card
    'next_payout_period' => 'Next payout · :period',
    'iban_masked' => 'IBAN :iban',
    'view_statement' => 'View statement →',
    'next_payout_empty' => 'No payouts yet. Your first payout appears once a statement is finalized.',
    // the payout-account card
    'payout_account' => 'Payout account',
    'manage' => 'Manage',
    // the link stays inside the sentence
    'no_bank_account' => 'No bank account yet. <a href=":url" style="color:var(--vy-accent-text);font-weight:600">Add one →</a>',
    'bank_transfer' => 'Bank transfer',
    'managed_by_owner' => 'Managed by the account owner.',
    // the next scheduled payout card
    'next_scheduled' => 'Next scheduled payout',
    'paused' => 'Paused',
    'schedule_paused' => 'Automated payouts are paused in your preferences below. Resume them to schedule your next payout.',
    'schedule_runs' => ':frequency · runs automatically on the next payout day.',
    'engine_off' => 'The automated engine is not yet switched on — this is the date it will use once enabled.',
    // payout preferences
    'preferences_title' => 'Payout preferences',
    'active' => 'Active',
    'frequency' => 'Frequency',
    // the hint stays inside the label, in its own lighter style
    'payout_day_hint' => 'Payout day <span style="text-transform:none;letter-spacing:0;color:var(--vy-faint)">(business day of month)</span>',
    'day_1st' => '1st',
    'day_2nd' => '2nd',
    'day_3rd' => '3rd',
    'day_4th' => '4th',
    'day_5th' => '5th',
    'minimum_payout' => 'Minimum payout',
    'minimum_none' => 'None',
    'payout_currency' => 'Payout currency',
    'currency_set_by_region' => 'Set by your settlement region',
    'pause_payouts' => 'Pause payouts',
    'pause_payouts_hint' => 'Hold funds in your Vyrobel balance',
    'preference_note' => 'Your stated preference. The actual payout date + release are confirmed with your operator and the payout rail.',
    'confirm_password' => 'Confirm with your password',
    'current_password' => 'Current password',
    'save_preferences' => 'Save preferences',
    // the read-only preferences (accountant / view-only)
    'payout_day' => 'Payout day',
    'payout_day_value' => ':day business day',
    'payouts_state' => 'Payouts',
];
