<?php

// app/Models/OwnerLedgerEntry.php — label(): the sentences the system writes into owner_ledger.description, said in the reader's
// language (App\Support\I18n\StoredEnglish). Each English value is EXACTLY what the writer stores (OwnerAttributionService,
// OwnerExpenseService, OwnerPayoutService, WithholdingService) with :name where it fills a value in — change one only together
// with its writer, or old rows stop matching and show as stored. :nights is a bare number; :pct a percentage without "%";
// :interval the agreement's fee interval code (monthly / quarterly / yearly); :category an expense category; :original and
// :description the reversed line / the expense's own text.

return [
    'room_revenue' => 'Room revenue · :nights night(s)',
    'commission' => 'Management commission :pct%',
    'ota_unverified' => 'OTA commission — unverified / pending channel reconciliation',
    'ota' => 'OTA commission on booking',
    'cleaning_fee' => 'Cleaning fee',
    'addons' => 'Add-ons',
    'guest_refund' => 'Guest refund adjustment',
    'not_attributed' => 'Not attributed — no governing management agreement on :date',
    'management_fee' => 'Management fee (:interval, pro-rated)',
    'withholding' => 'Withholding tax (:pct%)',
    'carried_forward' => 'Balance carried forward from statement :number',
    'expense' => 'Expense — :category: :description',
    'reversal' => 'Reversal — :original',
];
