<?php

// I18N-01 — es translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => 'Ninguna de esas unidades tiene un precio propio en ese periodo: no hay nada que quitar.',
    'choose_unit_to_price' => 'Seleccione al menos una unidad a la que fijar el precio.',
    'preview_first' => 'Previsualice el cambio antes de aplicarlo.',
    'read_only' => 'Su organización está en modo de solo lectura en este momento: los cambios de tarifas están en pausa.',
    'invalid_dates' => 'Seleccione fechas de inicio y de fin válidas.',
    'end_before_start' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
    'range_too_long' => 'Ese periodo es demasiado largo: aplique como máximo un año cada vez.',
    'choose_mode' => 'Elija si quiere fijar un precio o quitar los precios por unidad.',
    'invalid_rate' => 'Introduzca una tarifa por noche válida, p. ej., 175 o 175,50.',
    'choose_room_type' => 'Seleccione un tipo de habitación.',
    'no_rate_plan' => 'Ese tipo de habitación no tiene plan tarifario, así que no hay dónde guardar un precio.',
    'choose_unit' => 'Seleccione al menos una unidad.',
    'too_many_units' => 'Son demasiadas unidades para una sola operación.',
    'cleared_summary' => '{1} Se quitó el precio por unidad de :count unidad: vuelve a seguir la tarifa del tipo de habitación.|[0,*] Se quitó el precio por unidad de :count unidades: vuelven a seguir la tarifa del tipo de habitación.',
    'priced_summary' => 'Precio individual fijado para :units durante :nights.',
    'units_count' => '{1} :count unidad|[0,*] :count unidades',
    'nights_count' => '{1} :count noche|[0,*] :count noches',
];
