<?php

// I18N-01 — es translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'La estancia vuelve a estar confirmada y la habitación sigue reservada para las mismas noches.',
    'history_kept' => 'El check-in en sí sigue en el historial de la reserva. Ocurrió, y el registro lo refleja: la anulación se agrega junto a él.',
    'charges_kept' => '{1} :count cargo registrado desde el check-in, por un total de :amount, se mantiene en el folio. Deshacer una llegada no devuelve el dinero gastado: si también fue un error, quítelo del folio.|[0,*] :count cargos registrados desde el check-in, por un total de :amount, se mantienen en el folio. Deshacer una llegada no devuelve el dinero gastado: si también fueron un error, quítelos del folio.',
    'online_check_in_kept' => 'El check-in online que hizo el propio huésped sigue como completado. Lo rellenó de verdad, y esta acción no le pide que lo repita.',
    'already_reversed' => 'Ese check-in ya se deshizo hace un momento: la estancia vuelve a estar confirmada.',
    'refused_not_checked_in' => 'Solo se puede deshacer el check-in de una estancia con el check-in realizado; esta tiene el estado “:status”.',
];
