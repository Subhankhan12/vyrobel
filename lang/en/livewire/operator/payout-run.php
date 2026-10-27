<?php

// resources/views/livewire/operator/payout-run.blade.php — the operator's Payout run (/app/payouts/run).

return [
    'payout_run' => 'Payout run',
    'breadcrumb' => 'Breadcrumb',
    'owners' => 'Owners',
    // the toolbar line about this organisation's last run — the figures are counts, `when` is "2 days ago"
    'last_run_line' => 'Last run here <span class="vy-muted">· :created created · :paid paid · :held held · :when</span>',
    'no_run_yet_here' => 'No payout run yet here',
    'sepa_soon_title' => 'No SEPA export yet — the disbursement rail is test-mode',
    'sepa_soon' => 'Export SEPA · Soon',
    'run_payouts_now' => 'Run payouts now',
    // the confirm dialog of "Run payouts now"
    'run_confirm_title' => 'Run payouts now?',
    'run_confirm_sub_test' => 'In test mode.',
    'run_confirm_sub_live' => 'In live mode.',
    'run_confirm_button' => 'Run payouts',
    // :rail is the payout method's name in lower case ("bank transfer")
    'run_fact_creates' => 'Creates a payout for every owner here with a statement ready to pay, and pays each one by :rail.',
    'run_fact_manual' => 'A bank transfer is recorded, not sent: each payout is marked paid — make the transfers from your own bank.',
    'run_fact_tax_hold' => 'An owner with no tax details on file is held until they add them. Owners whose payouts are paused are skipped.',
    'run_fact_recorded' => 'Recorded as a payout run.',
    'engine_off' => 'The automatic payout engine is switched off, so payouts are not run in one go — create them and mark them paid below.',
    // the stat strip
    'ready_to_pay' => 'Ready to pay',
    'ready_sub' => '{1} :count finalized statement awaiting a payout|[0,*] :count finalized statements awaiting a payout',
    'ready_sub_per_currency' => '{1} :count finalized statement awaiting a payout · shown per currency (never summed across)|[0,*] :count finalized statements awaiting a payout · shown per currency (never summed across)',
    'pending_transfers' => 'Pending transfers',
    'pending_sub' => '{1} :count payout created, not yet paid|[0,*] :count payouts created, not yet paid',
    'paid_label' => 'Paid',
    'paid_sub' => '{1} :count payout genuinely marked paid|[0,*] :count payouts genuinely marked paid',
    // Ready to pay — the create-all form and its confirm dialog
    'create_all_confirm_title' => '{1} Create :count payout?|[0,*] Create :count payouts?',
    'create_all_confirm_sub' => 'One for each finalised statement ready to pay.',
    'create_all_button' => '{1} Create :count payout|[0,*] Create :count payouts',
    'create_all_fact_records' => 'Each records what you owe that owner — it moves no money.',
    'create_all_fact_waits_engine' => 'Each waits under Pending transfers until it is paid — by you, or by the next automatic run.',
    'create_all_fact_waits' => 'Each waits under Pending transfers until you mark it paid.',
    'fact_paused_skipped' => 'Owners whose payouts are paused are skipped.',
    'fact_audit_log' => 'Recorded in the audit log.',
    // table columns
    'col_owner' => 'Owner',
    'col_statement' => 'Statement',
    'col_period' => 'Period',
    'col_net_payable' => 'Net payable',
    'col_method' => 'Method',
    'col_amount' => 'Amount',
    'finalized_on' => 'Finalized :date',
    // one row's create-payout confirm dialog
    'create_one_confirm_title' => 'Create a payout for :owner?',
    'statement_number' => 'Statement #:number',
    'create_payout' => 'Create payout',
    'create_one_fact_records' => 'It records what you owe them — it moves no money.',
    'create_one_fact_waits_engine' => 'It waits under Pending transfers until it is paid — by you, or by the next automatic run.',
    'create_one_fact_waits' => 'It waits under Pending transfers until you mark it paid.',
    'ready_empty' => 'No finalized statement is waiting for a payout.',
    // Payouts paused
    'payouts_paused' => 'Payouts paused',
    'paused_note' => 'No payout is created or sent for these owners — not by the automatic run, not by hand — until their payouts are resumed on the owner’s page.',
    'open_owner' => 'Open owner',
    // Pending transfers — the mark-all form and its confirm dialog
    'mark_all_confirm_title' => '{1} Mark :count payout paid?|[0,*] Mark :count payouts paid?',
    'mark_all_confirm_sub' => 'Do this once every one of these transfers has left your own bank.',
    'mark_all_button' => 'Mark :count paid',
    'mark_all_fact_records' => 'Vyrobel records each payout and its statement as paid — it does not send money.',
    'paused_title' => 'Payouts to this owner are paused — resume them on the owner’s page',
    'paused' => 'Paused',
    // one row's mark-paid confirm dialog
    'mark_one_confirm_title' => 'Mark :amount to :owner as paid?',
    'mark_one_confirm_sub' => 'Do this once the transfer has left your own bank.',
    'mark_paid' => 'Mark paid',
    'mark_one_fact_records' => 'Vyrobel records the payout and its statement as paid — it does not send money.',
    'rail_not_connected' => 'Rail not connected',
    'pending_empty' => 'No transfer is waiting to be paid.',
    // Recent transfers
    'recent_transfers' => 'Recent transfers',
    'paid_on' => 'Paid :date',
    'failed_on' => 'Failed :date',
    'ref' => 'Ref :ref',
    'recent_empty' => 'No payout has settled yet.',
    // Run summary (the dark card)
    'run_summary' => 'Run summary',
    'last_run_here' => 'Last run here',
    'run_counts' => ':created created · :paid paid · :held held',
    'ran_in_test_mode' => 'Ran in test mode — no real funds moved.',
    // a run stored with any other mode name; :mode is that stored name
    'ran_in_mode' => 'Ran in :mode mode — no real funds moved.',
    'no_run_yet' => 'No run yet',
    'nothing_run' => 'Nothing has been run this period.',
    'summary_ready' => 'Ready',
    'summary_pending' => 'Pending',
    'summary_held' => 'Held',
    // How this run pays
    'how_this_run_pays' => 'How this run pays',
    'rail' => 'Rail',
    'manual_note' => 'Vyrobel does not move this money. Marking a transfer paid RECORDS a bank transfer you have already made from your own bank — it does not send one.',
    'cannot_disburse_note' => 'This rail cannot disburse, so the Mark-paid controls are disabled rather than offered as a guaranteed failure.',
    'pay_via_stripe_connect' => 'Pay via Stripe Connect',
    'soon' => 'Soon',
    'connect_note' => 'Stripe Connect disbursement is not connected — connected-account onboarding and KYC are an unverified live checkpoint, so no money can leave Vyrobel through it.',
    // On hold
    'on_hold' => 'On hold',
    'iban_masked' => 'IBAN :iban',
    'no_method' => 'No payout method on file — the owner adds their IBAN in the portal, verified before the first payout.',
    'request_iban' => 'Request IBAN',
    'hold_note' => 'Held amounts stay in the owner\'s ledger and carry forward until the block clears (#P2-1).',
    // Not payable
    'not_payable' => 'Not payable',
    'negative_net' => 'Nothing payable — a negative net carries forward to offset this owner\'s next statement.',
    'zero_net' => 'Nothing payable — the net for this period is zero.',
    'not_payable_empty' => 'Every finalized statement has a positive net payable.',
    // the confirm dialog's fallback when a form's text cannot be read
    'go_ahead_title' => 'Go ahead?',
    'go_ahead_button' => 'Go ahead',
];
