<?php

// I18N-01 — et translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Viibimine naaseb sisse registreeritud olekusse.',
    'pending_tasks_removed' => '{1}Eemaldatakse :count lahkumisjärgne koristustöö — pole veel alustatud.|[0,*]Eemaldatakse :count lahkumisjärgset koristustööd — pole veel alustatud.',
    'started_tasks_kept' => '{1}:count koristustöö on juba alustatud ja jäetakse puutumata — alustatud töö tühistamine on koristajate otsus, mitte tagasivõtmise ülesanne.|[0,*]:count koristustööd on juba alustatud ja jäetakse puutumata — alustatud töö tühistamine on koristajate otsus, mitte tagasivõtmise ülesanne.',
    'unit_stays_dirty' => 'Majutusüksus jääb märgituks koristamata. Külaline on taas toas, seega olek „Puhas“ ei pruugi enam tõele vastata.',
    'notifications_stay_delivered' => 'Töötajatele ja külalisele juba edastatud teavitused jäävad edastatuks.',
    'review_unknown' => 'Meil pole andmeid selle kohta, kas selle viibimise kohta saadeti arvustuse palve, sest külaline registreeriti välja enne, kui see süsteem hakkas neid salvestama. See võib olla saadetud või mitte.',
    'review_sent_on' => 'Arvustuse palve saadeti külalisele e-kirjaga kuupäeval :date. Saadetud kirja ei saa tagasi võtta.',
    'review_sent' => 'Arvustuse palve saadeti külalisele e-kirjaga. Saadetud kirja ei saa tagasi võtta.',
    'review_suppressed_by_operator' => 'Arvustuse palvet ei saadetud — sellel majutusasutusel on arvustuse palve e-kiri välja lülitatud.',
    'review_suppressed_by_guest' => 'Arvustuse palvet ei saadetud — külaline on palunud mitte saada arvustuse e-kirju.',
    'review_not_sent' => 'Arvustuse palvet ei saadetud — kontaktaadressi polnud või saatmine ebaõnnestus.',
    'refused_not_checked_out' => 'Väljaregistreerimist saab tagasi võtta ainult välja registreeritud viibimisel; selle viibimise olek: :status.',
];
