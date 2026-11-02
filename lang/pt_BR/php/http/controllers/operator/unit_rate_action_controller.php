<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => 'Nenhuma dessas unidades tem preço próprio nesse período — não há nada para remover.',
    'choose_unit_to_price' => 'Escolha pelo menos uma unidade para definir o preço.',
    'preview_first' => 'Visualize a alteração antes de aplicá-la.',
    'read_only' => 'Sua organização está em modo somente leitura no momento — as alterações de tarifas estão em pausa.',
    'invalid_dates' => 'Escolha datas de início e fim válidas.',
    'end_before_start' => 'A data de fim não pode ser anterior à data de início.',
    'range_too_long' => 'Esse período é longo demais — aplique no máximo um ano por vez.',
    'choose_mode' => 'Escolha entre definir um preço ou remover os preços por unidade.',
    'invalid_rate' => 'Informe uma diária válida, ex.: 175 ou 175,50.',
    'choose_room_type' => 'Escolha um tipo de quarto.',
    'no_rate_plan' => 'Esse tipo de quarto não tem plano tarifário, então não há onde gravar um preço.',
    'choose_unit' => 'Escolha pelo menos uma unidade.',
    'too_many_units' => 'São unidades demais para uma única operação.',
    'cleared_summary' => '{1} Preço por unidade removido de :count unidade — ela volta a seguir a tarifa do tipo de quarto.|[0,*] Preço por unidade removido de :count unidades — elas voltam a seguir a tarifa do tipo de quarto.',
    'priced_summary' => 'Preço individual definido para :units em :nights.',
    'units_count' => '{1} :count unidade|[0,*] :count unidades',
    'nights_count' => '{1} :count noite|[0,*] :count noites',
];
