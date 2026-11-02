<?php

// I18N-01 — it translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'I pagamenti con carta passano da Stripe (modalità di test) — saranno disponibili con il passaggio alle chiavi live. Per incassare ora un pagamento alla reception, registrare contanti o un bonifico.',
    'payment_amount_invalid' => 'Inserire un importo di pagamento maggiore di zero.',
    'payment_recorded' => 'Pagamento registrato.',
    'charge_amount_invalid' => 'Inserire un importo di addebito maggiore di zero.',
    'charge_added' => 'Addebito aggiunto.',
    'refund_only_settled' => 'Si può rimborsare solo un pagamento completato.',
    'already_fully_refunded' => 'Questo pagamento è già stato rimborsato per intero.',
    'refund_amount_invalid' => 'Inserire un importo di rimborso maggiore di zero.',
    'refund_exceeds' => 'Rimborso rifiutato — su questo pagamento sono ancora rimborsabili solo :amount :currency.',
    'refund_issued' => 'Rimborso emesso.',
    'billed_to_account' => 'Addebitato a :account. Il saldo resta dovuto finché l’azienda non paga.',
    'returned_to_guest' => 'Riportato sul conto dell’ospite.',
];
