<?php

// I18N-01 — nl translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Dat is geen geldige reden om toch uit te checken.',
    'override_note_required' => 'Bij de reden “Anders” is een notitie nodig over wat er is gebeurd.',
    'refused_confirmed' => 'Een bevestigde reservering kan niet worden uitgecheckt: een verblijf moet eerst zijn ingecheckt voordat het kan worden uitgecheckt. Heeft de gast verbleven en vertrekt deze eerder, check de gast dan eerst in en daarna uit. Is de gast nooit aangekomen, markeer de reservering dan als no-show.',
    'refused_closed' => 'Een reservering met de status “:status” kan niet worden uitgecheckt: de reservering is afgesloten en er heeft geen verblijf plaatsgevonden. Een gast die toch is gekomen, heeft een nieuwe reservering nodig.',
    'refused_other' => 'Een reservering met de status “:status” kan niet worden uitgecheckt.',
];
