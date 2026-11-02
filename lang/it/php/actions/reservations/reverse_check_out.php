<?php

// I18N-01 — it translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Il soggiorno torna allo stato «Check-in effettuato».',
    'pending_tasks_removed' => '{1} Verrà eliminata :count attività di pulizia di partenza, non ancora iniziata.|[0,*] Verranno eliminate :count attività di pulizia di partenza, non ancora iniziate.',
    'started_tasks_kept' => '{1} :count attività di pulizia è già stata avviata e non verrà toccata: annullare un lavoro già iniziato da qualcuno è una decisione del reparto pulizie, non un semplice annullamento.|[0,*] :count attività di pulizia sono già state avviate e non verranno toccate: annullare un lavoro già iniziato da qualcuno è una decisione del reparto pulizie, non un semplice annullamento.',
    'unit_stays_dirty' => 'L’unità resta segnata come sporca. L’ospite è di nuovo in camera, quindi «pulita» potrebbe non essere vero.',
    'notifications_stay_delivered' => 'Le notifiche già recapitate al personale e all’ospite restano recapitate.',
    'review_unknown' => 'Non risulta se per questo soggiorno sia stata inviata una richiesta di recensione, perché il check-out è avvenuto prima che il sistema iniziasse a registrarle. Potrebbe essere stata inviata oppure no.',
    'review_sent_on' => 'Una richiesta di recensione è stata inviata all’ospite via e-mail in data :date. Non può essere ritirata.',
    'review_sent' => 'Una richiesta di recensione è stata inviata all’ospite via e-mail. Non può essere ritirata.',
    'review_suppressed_by_operator' => 'Nessuna richiesta di recensione inviata: questa struttura ha disattivato l’e-mail di richiesta di recensione.',
    'review_suppressed_by_guest' => 'Nessuna richiesta di recensione inviata: l’ospite ha chiesto di non ricevere e-mail per le recensioni.',
    'review_not_sent' => 'Nessuna richiesta di recensione inviata: mancava un indirizzo di contatto oppure l’invio non è riuscito.',
    'refused_not_checked_out' => 'Si può annullare il check-out solo di un soggiorno con check-out effettuato; lo stato di questo soggiorno è «:status».',
];
