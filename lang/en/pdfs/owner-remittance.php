<?php

// resources/views/pdfs/owner-remittance.blade.php — the owner payout remittance advice PDF.

return [
    'eyebrow' => 'Remittance advice',
    'heading' => 'Payout remittance',
    'iban' => 'IBAN :iban',
    'paid_on_inline' => 'paid :date',
    'statement_number' => 'Statement #:number',
    'how_calculated' => 'How it\'s calculated',
    'amount' => 'Amount',
    'provisional' => 'Provisional',
    // the label and the amount stay in one value so each language places the colon as its typography needs
    'reconcile_total' => ':label: <strong>:amount</strong>',
    'includes_provisional' => '— includes provisional lines pending verification.',
    'payment_details' => 'Payment details',
    'beneficiary' => 'Beneficiary',
    'account' => 'Account',
    'method' => 'Method',
    'paid_on' => 'Paid on',
    'reference' => 'Reference',
    'currency' => 'Currency',
    'fx' => 'FX',
    'fx_source' => '(source: :source)',
    'failure' => 'Failure',
    'footer_with_statement' => 'This remittance advice accompanies statement #:number and reconciles to the figures shown in the owner portal.',
    'footer' => 'This remittance advice reconciles to the figures shown in the owner portal.',
];
