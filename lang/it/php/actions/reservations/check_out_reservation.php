<?php

// I18N-01 — it translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Questo motivo di forzatura del check-out non è riconosciuto.',
    'override_note_required' => 'Una forzatura con motivo «Altro» richiede una nota che spieghi cosa è successo.',
    'refused_confirmed' => 'Impossibile effettuare il check-out di una prenotazione confermata: il check-out è possibile solo dopo il check-in. Se l’ospite ha soggiornato e parte in anticipo, effettui prima il check-in e poi il check-out. In caso di mancato arrivo, segni invece la prenotazione come no-show.',
    'refused_closed' => 'Impossibile effettuare il check-out di una prenotazione con lo stato «:status»: la prenotazione è chiusa e non c’è stato alcun soggiorno. Se l’ospite si presenta comunque, serve una nuova prenotazione.',
    'refused_other' => 'Impossibile effettuare il check-out di una prenotazione con lo stato «:status».',
];
