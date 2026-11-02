<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Escolha um plano tarifário.',
    'name_required' => 'Dê um nome à época.',
    'name_too_long' => 'O nome da época pode ter no máximo 80 caracteres.',
    'choose_first_night' => 'Escolha a primeira noite.',
    'choose_last_night' => 'Escolha a última noite.',
    'last_before_first' => 'A última noite não pode ser anterior à primeira noite.',
    'price_required' => 'Introduza um preço por noite.',
    'read_only' => 'A sua organização está em modo só de leitura neste momento — as alterações de tarifas estão em pausa.',
    'price_out_of_range' => 'Introduza um preço por noite superior a zero e não superior a 1 000 000.',
    'season_saved' => 'Época guardada. Reveja o que vai mudar e depois aplique-a para gravar essas noites.',
    'already_applied' => 'Esta época já foi aplicada, por isso não pode ser editada — as tarifas por noite que gravou substituíram o que lá estava, e os preços anteriores não ficaram registados. Em vez disso, crie uma nova época para essas noites.',
    'season_updated' => 'Época atualizada. Reveja o que vai mudar e depois aplique-a para gravar essas noites.',
    'deleted_after_apply' => 'Época «:season» eliminada. ⚠️ As tarifas por noite que já tinha aplicado mantêm-se — eliminar uma época não repõe os preços que substituiu. Defina essas noites manualmente se estiverem erradas.',
    'deleted_not_applied' => 'Época «:season» eliminada. Não tinha sido aplicada, por isso nenhuma tarifa mudou.',
    'applied' => 'Época :season aplicada — preços regravados em :nights, em :units.',
    'nights_count' => '{1} :count noite|[0,*] :count noites',
    'units_count' => '{1} :count unidade|[0,*] :count unidades',
    'overlapping_reapplied' => '{1} :count época sobreposta foi reaplicada por ordem de prioridade.|[0,*] :count épocas sobrepostas foram reaplicadas por ordem de prioridade.',
    'pick_unit_to_continue' => 'Escolha uma unidade para continuar.',
    'stay_nights_unpriced' => '{1} :count noite da estadia continua sem preço.|[0,*] :count noites da estadia continuam sem preço.',
];
