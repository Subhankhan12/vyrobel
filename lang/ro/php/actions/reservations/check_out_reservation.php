<?php

// I18N-01 — ro translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Acesta nu este un motiv recunoscut pentru check-out cu sold neachitat.',
    'override_note_required' => 'Pentru motivul „Alt motiv”, adăugați o notă care să explice ce s-a întâmplat.',
    'refused_confirmed' => 'Nu se poate efectua check-out-ul pentru o rezervare confirmată: un sejur trebuie să aibă check-in înainte de check-out. Dacă oaspetele a stat și pleacă mai devreme, efectuați mai întâi check-in-ul, apoi check-out-ul. Dacă nu a sosit niciodată, marcați rezervarea ca neprezentare.',
    'refused_closed' => 'Nu se poate efectua check-out-ul pentru o rezervare cu statusul „:status”: rezervarea este închisă și nu a avut loc niciun sejur. Un oaspete care a venit totuși are nevoie de o rezervare nouă.',
    'refused_other' => 'Nu se poate efectua check-out-ul pentru o rezervare cu statusul „:status”.',
];
