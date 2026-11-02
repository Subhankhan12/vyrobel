<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => 'Żadna z tych jednostek nie ma własnej ceny w tym zakresie – nie ma czego usuwać.',
    'choose_unit_to_price' => 'Wybierz co najmniej jedną jednostkę, aby ustalić cenę.',
    'preview_first' => 'Przed zastosowaniem zmiany wyświetl jej podgląd.',
    'read_only' => 'Organizacja jest obecnie tylko do odczytu – zmiany cen są wstrzymane.',
    'invalid_dates' => 'Wybierz prawidłową datę początkową i końcową.',
    'end_before_start' => 'Data końcowa nie może być wcześniejsza niż data początkowa.',
    'range_too_long' => 'Ten zakres dat jest zbyt długi – zmiany można stosować maksymalnie dla jednego roku naraz.',
    'choose_mode' => 'Wybierz, czy ustawić cenę, czy usunąć osobne ceny jednostek.',
    'invalid_rate' => 'Wpisz prawidłową cenę za noc, np. 175 lub 175,50.',
    'choose_room_type' => 'Wybierz typ pokoju.',
    'no_rate_plan' => 'Ten typ pokoju nie ma planu cenowego, więc nie ma gdzie zapisać ceny.',
    'choose_unit' => 'Wybierz co najmniej jedną jednostkę.',
    'too_many_units' => 'To zbyt wiele jednostek na jedną operację.',
    'cleared_summary' => '{1}Usunięto osobną cenę :count jednostki – znów obowiązuje cena typu pokoju.|Usunięto osobne ceny :count jednostek – znów obowiązuje cena typu pokoju.|Usunięto osobne ceny :count jednostek – znów obowiązuje cena typu pokoju.',
    'priced_summary' => 'Ustalono osobną cenę (:units, :nights).',
    'units_count' => '{1}:count jednostka|:count jednostki|:count jednostek',
    'nights_count' => '{1}:count noc|:count noce|:count nocy',
];
