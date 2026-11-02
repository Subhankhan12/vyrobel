<?php

// I18N-01 — it translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Impossibile effettuare il check-in di una prenotazione cancellata: la prenotazione è chiusa e la camera potrebbe essere già stata rivenduta. Per un ospite che ritorna serve una nuova prenotazione.',
    'refused_no_show' => 'Impossibile effettuare il check-in di una prenotazione in no-show: la prenotazione è chiusa e le sue notti sono state rimesse in vendita. Per sistemare l’ospite in una camera, crei una prenotazione con il pulsante «Nuova prenotazione» nella pagina «Reception»: la disponibilità viene ricontrollata prima della prenotazione. Il no-show e l’eventuale penale già addebitata restano nella scheda dell’ospite.',
    'refused_checked_out' => 'Impossibile effettuare il check-in di una prenotazione con check-out effettuato: la partenza è già stata registrata e gestita. L’annullamento di un check-out non è disponibile.',
    'refused_tentative' => 'Impossibile effettuare il check-in di una prenotazione in opzione: la camera è bloccata ma non confermata, quindi a questo ospite non è ancora stato garantito il soggiorno. Prema prima il pulsante «Conferma opzione» su questa prenotazione, poi effettui il check-in. Le notti sono già bloccate da questa prenotazione, quindi la conferma non richiede altro e non può fallire per mancanza di disponibilità.',
    'refused_other' => 'Impossibile effettuare il check-in di una prenotazione con lo stato «:status».',
];
