<?php

// I18N-01 — ro translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Nu se poate efectua check-in-ul pentru o rezervare anulată: rezervarea este închisă, iar camera poate fi deja revândută. Un oaspete care revine are nevoie de o rezervare nouă.',
    'refused_no_show' => 'Nu se poate efectua check-in-ul pentru o rezervare cu neprezentare: rezervarea este închisă, iar nopțile ei au fost repuse în vânzare. Pentru a caza acest oaspete, creați o rezervare cu butonul „Rezervare nouă” din Recepție — aceasta verifică din nou disponibilitatea înainte de a rezerva. Neprezentarea și orice taxă deja debitată pentru ea rămân în fișa oaspetelui.',
    'refused_checked_out' => 'Nu se poate efectua check-in-ul pentru o rezervare cu check-out efectuat: plecarea a fost deja înregistrată și procesată. Anularea unui check-out nu este disponibilă.',
    'refused_tentative' => 'Nu se poate efectua check-in-ul pentru o rezervare opțională: camera este reținută, dar nu este confirmată, deci nimeni nu i-a promis încă acestui oaspete sejurul. Apăsați mai întâi butonul „Confirmare opțiune” al acestei rezervări, apoi efectuați check-in-ul. Nopțile sunt deja reținute de această rezervare, deci confirmarea nu mai blochează nimic în plus și nu poate eșua din cauza disponibilității.',
    'refused_other' => 'Nu se poate efectua check-in-ul pentru o rezervare cu statusul „:status”.',
];
