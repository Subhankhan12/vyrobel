<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Wybierz plan cenowy.',
    'name_required' => 'Nadaj sezonowi nazwę.',
    'name_too_long' => 'Nazwa sezonu może mieć maksymalnie 80 znaków.',
    'choose_first_night' => 'Wybierz pierwszą noc.',
    'choose_last_night' => 'Wybierz ostatnią noc.',
    'last_before_first' => 'Ostatnia noc nie może być wcześniejsza niż pierwsza.',
    'price_required' => 'Wpisz cenę za noc.',
    'read_only' => 'Organizacja jest obecnie tylko do odczytu – zmiany cen są wstrzymane.',
    'price_out_of_range' => 'Wpisz cenę za noc większą od zera i nie wyższą niż 1 000 000.',
    'season_saved' => 'Zapisano sezon. Sprawdź zakres zmian, a następnie zastosuj sezon, aby zapisać ceny na te noce.',
    'already_applied' => 'Ten sezon został już zastosowany, więc nie można go edytować – zapisane przez niego ceny za noc zastąpiły poprzednie, a poprzednich cen nigdzie nie zachowano. Zamiast tego utwórz nowy sezon na te noce.',
    'season_updated' => 'Zaktualizowano sezon. Sprawdź zakres zmian, a następnie zastosuj sezon, aby zapisać ceny na te noce.',
    'deleted_after_apply' => 'Usunięto sezon „:season”. ⚠️ Zastosowane już przez niego ceny za noc pozostają bez zmian – usunięcie sezonu nie przywraca zastąpionych cen. Jeśli są nieprawidłowe, ustaw ceny na te noce samodzielnie.',
    'deleted_not_applied' => 'Usunięto sezon „:season”. Nie był zastosowany, więc żadne ceny się nie zmieniły.',
    'applied' => 'Zastosowano sezon :season – nadpisano ceny (:nights, :units).',
    'nights_count' => '{1}:count noc|:count noce|:count nocy',
    'units_count' => '{1}:count jednostka|:count jednostki|:count jednostek',
    'overlapping_reapplied' => '{1}Ponownie zastosowano :count nakładający się sezon w kolejności pierwszeństwa.|Ponownie zastosowano :count nakładające się sezony w kolejności pierwszeństwa.|Ponownie zastosowano :count nakładających się sezonów w kolejności pierwszeństwa.',
    'pick_unit_to_continue' => 'Wybierz jednostkę, aby kontynuować.',
    'stay_nights_unpriced' => '{1}:count noc pobytu nadal nie ma ceny.|:count noce pobytu nadal nie mają ceny.|:count nocy pobytu nadal nie ma ceny.',
];
