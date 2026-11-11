<?php

// I18N-01 — it translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Non è possibile assegnare un’unità a :owner: non ha ancora accettato l’invito oppure il suo profilo di proprietario è stato rimosso. Si può assegnare un’unità solo a un proprietario attivo.',
    'unit_already_owned' => '«:unit» appartiene già a :owner, quindi qui non è possibile assegnare l’unità a un’altra persona. Si può assegnare solo un’unità senza proprietario.',
    'unit_owned_by_another' => 'un altro proprietario',
    'date_invalid' => 'Selezionare una data valida da cui assegnare l’unità.',
    'date_in_past' => 'Selezionare oggi o un giorno successivo. Un giorno già trascorso modificherebbe guadagni già calcolati.',
    'date_too_far' => 'Selezionare un giorno entro i prossimi :days giorni.',
    'no_agreement' => ':owner non ha alcun contratto di gestione che copra «:unit» alla data :date. Prima creare per :owner un contratto di gestione che copra questa unità dalla data :date, poi assegnare l’unità.',
    'stay_across_date' => 'In «:unit» c’è il soggiorno di un ospite (arrivo: :from · partenza: :to) a cavallo della data :date. Selezionare il giorno di partenza (:to) o un giorno successivo, così che nessun soggiorno sia diviso tra Lei e :owner.',
    'future_stays_with_another_owner' => '«:unit» ha soggiorni a partire dalla data :date già calcolati per un altro proprietario (:owners). Quei soggiorni restano all’altro proprietario, quindi l’unità non può essere assegnata da quel giorno.',
];
