<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Escolha um plano tarifário.',
    'name_required' => 'Dê um nome à temporada.',
    'name_too_long' => 'O nome da temporada deve ter no máximo 80 caracteres.',
    'choose_first_night' => 'Escolha a primeira noite.',
    'choose_last_night' => 'Escolha a última noite.',
    'last_before_first' => 'A última noite não pode ser anterior à primeira.',
    'price_required' => 'Informe um preço por noite.',
    'read_only' => 'Sua organização está em modo somente leitura no momento — as alterações de tarifas estão em pausa.',
    'price_out_of_range' => 'Informe um preço por noite maior que zero e de no máximo 1.000.000.',
    'season_saved' => 'Temporada salva. Revise o impacto e depois aplique-a para gravar essas noites.',
    'already_applied' => 'Esta temporada já foi aplicada, por isso não pode ser editada — as diárias que ela gravou substituíram o que havia antes, e os preços anteriores não foram registrados. Crie uma nova temporada para essas noites.',
    'season_updated' => 'Temporada atualizada. Revise o impacto e depois aplique-a para gravar essas noites.',
    'deleted_after_apply' => 'Temporada “:season” excluída. ⚠️ As diárias que ela já aplicou não mudam — excluir uma temporada não restaura os preços que ela substituiu. Ajuste essas noites manualmente se estiverem erradas.',
    'deleted_not_applied' => 'Temporada “:season” excluída. Ela não tinha sido aplicada, então nenhuma tarifa mudou.',
    'applied' => 'Temporada :season aplicada — preços regravados para :nights em :units.',
    'nights_count' => '{1} :count noite|[0,*] :count noites',
    'units_count' => '{1} :count unidade|[0,*] :count unidades',
    'overlapping_reapplied' => '{1} :count temporada sobreposta foi reaplicada na ordem de precedência.|[0,*] :count temporadas sobrepostas foram reaplicadas na ordem de precedência.',
    'pick_unit_to_continue' => 'Escolha uma unidade para continuar.',
    'stay_nights_unpriced' => '{1} :count noite da estadia ainda está sem preço.|[0,*] :count noites da estadia ainda estão sem preço.',
];
