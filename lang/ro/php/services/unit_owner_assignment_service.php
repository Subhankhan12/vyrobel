<?php

// I18N-01 — ro translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Nu se poate atribui o unitate către :owner — nu și-a acceptat încă invitația sau a fost eliminat. Doar un proprietar activ poate primi o unitate.',
    'unit_already_owned' => 'Unitatea „:unit” este deja deținută de :owner, deci nu poate fi atribuită altcuiva aici. Se poate atribui doar o unitate fără proprietar.',
    'unit_owned_by_another' => 'un alt proprietar',
    'date_invalid' => 'Alegeți o dată reală de la care se atribuie unitatea.',
    'date_in_past' => 'Alegeți azi sau o zi ulterioară. O zi deja trecută ar modifica câștiguri care sunt deja calculate.',
    'date_too_far' => 'Alegeți o zi din următoarea perioadă (:days z).',
    'no_agreement' => 'Proprietarul :owner nu are niciun contract de administrare care să acopere unitatea „:unit” pe :date. Mai întâi creați pentru proprietarul :owner un contract de administrare care acoperă această unitate din :date, apoi atribuiți-i unitatea.',
    'stay_across_date' => 'Un oaspete are un sejur în unitatea „:unit” din :from până la :to, care cuprinde data :date. Alegeți data :to sau o zi ulterioară, astfel încât niciun sejur să nu fie împărțit între dumneavoastră și proprietarul :owner.',
    'future_stays_with_another_owner' => 'Unitatea „:unit” are sejururi din :date sau de după această dată, ale căror câștiguri sunt deja calculate pentru un alt proprietar (:owners). Acestea rămân la acesta, așa că unitatea nu poate fi atribuită din acea zi.',
];
