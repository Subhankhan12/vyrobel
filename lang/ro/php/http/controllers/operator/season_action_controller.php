<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Alegeți un plan tarifar.',
    'name_required' => 'Dați un nume sezonului.',
    'name_too_long' => 'Numele sezonului poate avea cel mult 80 de caractere.',
    'choose_first_night' => 'Alegeți prima noapte.',
    'choose_last_night' => 'Alegeți ultima noapte.',
    'last_before_first' => 'Ultima noapte nu poate fi înaintea primei nopți.',
    'price_required' => 'Introduceți un preț pe noapte.',
    'read_only' => 'Organizația este acum doar în citire — modificările de tarif sunt în pauză.',
    'price_out_of_range' => 'Introduceți un preț pe noapte mai mare decât zero și de cel mult 1.000.000.',
    'season_saved' => 'Sezonul a fost salvat. Verificați ce ar modifica, apoi aplicați-l pentru a scrie prețurile acelor nopți.',
    'already_applied' => 'Acest sezon a fost deja aplicat, deci nu mai poate fi editat — tarifele pe noapte scrise de el le-au înlocuit pe cele existente, iar prețurile anterioare nu au fost înregistrate nicăieri. Creați în schimb un sezon nou pentru acele nopți.',
    'season_updated' => 'Sezonul a fost actualizat. Verificați ce ar modifica, apoi aplicați-l pentru a scrie prețurile acelor nopți.',
    'deleted_after_apply' => 'Sezonul „:season” a fost șters. ⚠️ Tarifele pe noapte pe care le-a aplicat deja rămân neschimbate — ștergerea unui sezon nu restabilește prețurile pe care le-a înlocuit. Dacă sunt greșite, setați manual prețurile acelor nopți.',
    'deleted_not_applied' => 'Sezonul „:season” a fost șters. Nu fusese aplicat, deci niciun tarif nu s-a modificat.',
    'applied' => 'Sezonul :season a fost aplicat — prețuri rescrise pe :nights, în :units.',
    'nights_count' => '{1}:count noapte|:count nopți|:count de nopți',
    'units_count' => '{1}:count unitate|:count unități|:count de unități',
    'overlapping_reapplied' => '{1}:count sezon suprapus a fost reaplicat în ordinea priorității.|:count sezoane suprapuse au fost reaplicate în ordinea priorității.|:count de sezoane suprapuse au fost reaplicate în ordinea priorității.',
    'pick_unit_to_continue' => 'Alegeți o unitate pentru a continua.',
    'stay_nights_unpriced' => '{1}:count noapte din sejur încă nu are preț.|:count nopți din sejur încă nu au preț.|:count de nopți din sejur încă nu au preț.',
];
