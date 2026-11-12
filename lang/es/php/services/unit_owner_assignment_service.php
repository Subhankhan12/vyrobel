<?php

// I18N-01 — es translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':owner no puede recibir una unidad: todavía no ha aceptado su invitación o se le ha quitado como propietario. Solo se puede asignar una unidad a un propietario activo.',
    'unit_on_pending_invitation' => 'La unidad “:unit” figura en una invitación de propietario que todavía no se ha aceptado. Pasa a ser de ese propietario cuando la acepte; para asignarla a otra persona, cancele antes la invitación.',
    'unit_already_owned' => 'La unidad “:unit” ya pertenece a :owner, así que aquí no se puede asignar a otra persona. Solo se puede asignar una unidad sin propietario.',
    'unit_owned_by_another' => 'otro propietario',
    'date_invalid' => 'Elija una fecha válida a partir de la cual asignar la unidad.',
    'date_in_past' => 'Elija hoy o un día posterior. Un día que ya pasó cambiaría ingresos que ya están calculados.',
    'date_too_far' => 'Elija un día dentro de los próximos :days días.',
    'no_agreement' => ':owner no tiene ningún contrato que cubra la unidad “:unit” el :date. Primero agregue a :owner un contrato que cubra esta unidad desde el :date y, después, asígnele la unidad.',
    'stay_across_date' => 'Un huésped tiene una estancia en la unidad “:unit” del :from al :to, y el :date cae dentro de esa estancia. Elija el :to o un día posterior para que ninguna estancia quede repartida entre usted y :owner.',
    'future_stays_with_another_owner' => 'La unidad “:unit” tiene estancias a partir del :date que ya están calculadas para otro propietario (:owners). Esas estancias no cambian de propietario, así que no se puede asignar la unidad desde ese día.',
];
