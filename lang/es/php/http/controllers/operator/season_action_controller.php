<?php

// I18N-01 — es translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Seleccione un plan tarifario.',
    'name_required' => 'Ponga un nombre a la temporada.',
    'name_too_long' => 'El nombre de la temporada puede tener como máximo 80 caracteres.',
    'choose_first_night' => 'Seleccione la primera noche.',
    'choose_last_night' => 'Seleccione la última noche.',
    'last_before_first' => 'La última noche no puede ser anterior a la primera noche.',
    'price_required' => 'Introduzca un precio por noche.',
    'read_only' => 'Su organización está en modo de solo lectura en este momento: los cambios de tarifas están en pausa.',
    'price_out_of_range' => 'Introduzca un precio por noche mayor que cero y que no supere 1.000.000.',
    'season_saved' => 'Se guardó la temporada. Revise a qué afecta y después aplíquela para escribir los precios de esas noches.',
    'already_applied' => 'Esta temporada ya se aplicó, así que no se puede editar: las tarifas por noche que escribió sustituyeron lo que había y no quedó registro de los precios anteriores. Cree en su lugar una nueva temporada para esas noches.',
    'season_updated' => 'Se actualizó la temporada. Revise a qué afecta y después aplíquela para escribir los precios de esas noches.',
    'deleted_after_apply' => 'Se eliminó la temporada “:season”. ⚠️ Las tarifas por noche que ya aplicó no cambian: eliminar una temporada no restaura los precios que sustituyó. Si son incorrectos, fije usted mismo los precios de esas noches.',
    'deleted_not_applied' => 'Se eliminó la temporada “:season”. No se había aplicado, así que no cambió ninguna tarifa.',
    'applied' => 'Se aplicó la temporada :season: se reescribieron los precios de :nights en :units.',
    'nights_count' => '{1} :count noche|[0,*] :count noches',
    'units_count' => '{1} :count unidad|[0,*] :count unidades',
    'overlapping_reapplied' => '{1} Se volvió a aplicar :count temporada superpuesta por orden de prioridad.|[0,*] Se volvieron a aplicar :count temporadas superpuestas por orden de prioridad.',
    'pick_unit_to_continue' => 'Seleccione una unidad para continuar.',
    'stay_nights_unpriced' => '{1} :count noche de la estancia todavía no tiene precio.|[0,*] :count noches de la estancia todavía no tienen precio.',
];
