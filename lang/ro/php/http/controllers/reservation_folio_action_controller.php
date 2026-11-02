<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Plățile cu cardul trec prin Stripe (modul de testare) — vor fi disponibile la trecerea în modul real. Pentru a încasa acum o plată la recepție, înregistrați numerar sau un transfer bancar.',
    'payment_amount_invalid' => 'Introduceți o sumă de plată mai mare decât zero.',
    'payment_recorded' => 'Plata a fost înregistrată.',
    'charge_amount_invalid' => 'Introduceți o sumă a debitului mai mare decât zero.',
    'charge_added' => 'Debitul a fost adăugat.',
    'refund_only_settled' => 'Doar o plată finalizată poate fi rambursată.',
    'already_fully_refunded' => 'Această plată a fost deja rambursată integral.',
    'refund_amount_invalid' => 'Introduceți o sumă de rambursat mai mare decât zero.',
    'refund_exceeds' => 'Rambursarea a fost refuzată — pentru această plată se mai pot rambursa doar :amount :currency.',
    'refund_issued' => 'Rambursarea a fost efectuată.',
    'billed_to_account' => 'Facturat către :account. Soldul rămâne de plată până când este achitat.',
    'returned_to_guest' => 'Mutat înapoi pe nota de plată a oaspetelui.',
];
