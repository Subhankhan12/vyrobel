<?php

// I18N-01 — it translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Il soggiorno torna allo stato confermato e la camera resta riservata per le stesse notti.',
    'history_kept' => 'Il check-in resta nella cronologia della prenotazione. È avvenuto e la cronologia lo riporta: l’annullamento viene aggiunto accanto.',
    'charges_kept' => '{1} :count addebito registrato dopo il check-in, per un totale di :amount, resta sul conto. Annullare un arrivo non annulla le spese: se anche questo addebito è un errore, rimuoverlo dal conto.|[0,*] :count addebiti registrati dopo il check-in, per un totale di :amount, restano sul conto. Annullare un arrivo non annulla le spese: se anche questi addebiti sono un errore, rimuoverli dal conto.',
    'online_check_in_kept' => 'Il check-in online dell’ospite resta completato. L’ospite lo ha davvero compilato e non dovrà compilarlo di nuovo.',
    'already_reversed' => 'Il check-in è già stato annullato un attimo fa: il soggiorno è tornato allo stato confermato.',
    'refused_not_checked_in' => 'Si può annullare il check-in solo di un soggiorno con check-in effettuato; lo stato di questo soggiorno è «:status».',
];
