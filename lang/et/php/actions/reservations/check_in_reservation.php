<?php

// I18N-01 — et translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Tühistatud broneeringut ei saa sisse registreerida: broneering on lõppenud ja tuba võib olla juba uuesti müüdud. Külaline, kes siiski saabub, vajab uut broneeringut.',
    'refused_no_show' => 'Kohale mitteilmunud broneeringut ei saa sisse registreerida: broneering on lõppenud ja selle ööd on müüki tagasi vabastatud. Külalise majutamiseks kasutage vastuvõtus valikut „Ilma broneeringuta“ — see kontrollib enne broneerimist saadavust uuesti. Kohale mitteilmumine ja sellelt juba võetud tasu jäävad külalise andmetesse.',
    'refused_checked_out' => 'Välja registreeritud broneeringut ei saa sisse registreerida: lahkumine on juba registreeritud ja selle põhjal on tegutsetud. Kui väljaregistreerimine oli eksitus, kasutage esmalt broneeringul nuppu „Võta väljaregistreerimine tagasi“.',
    'refused_tentative' => 'Eelbroneeringut ei saa sisse registreerida: tuba on reserveeritud, kuid kinnitamata, seega pole külalisele viibimist veel lubatud. Vajutage esmalt sellel broneeringul nuppu „Kinnita eelbroneering“ ja registreerige külaline seejärel sisse. Ööd on selle broneeringuga juba reserveeritud, seega kinnitamine ei võta midagi lisaks ega saa saadavuse tõttu ebaõnnestuda.',
    'refused_other' => 'Broneeringut ei saa sisse registreerida (olek: :status).',
];
