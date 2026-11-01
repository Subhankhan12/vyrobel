<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => 'Aucune de ces unités n’a de prix propre sur cette période — il n’y a rien à effacer.',
    'choose_unit_to_price' => 'Choisissez au moins une unité à tarifer.',
    'preview_first' => 'Prévisualisez la modification avant de l’appliquer.',
    'read_only' => 'Votre organisation est en lecture seule pour le moment — les modifications de tarifs sont suspendues.',
    'invalid_dates' => 'Choisissez des dates de début et de fin valides.',
    'end_before_start' => 'La date de fin ne peut pas précéder la date de début.',
    'range_too_long' => 'Cette période est trop longue — veuillez appliquer au maximum un an à la fois.',
    'choose_mode' => 'Choisissez de fixer un prix ou d’effacer les prix par unité.',
    'invalid_rate' => 'Saisissez un tarif par nuit valide, ex. 175 ou 175,50.',
    'choose_room_type' => 'Choisissez un type de chambre.',
    'no_rate_plan' => 'Ce type de chambre n’a pas de plan tarifaire : le prix ne peut être enregistré nulle part.',
    'choose_unit' => 'Choisissez au moins une unité.',
    'too_many_units' => 'Trop d’unités pour une seule opération.',
    'cleared_summary' => '{1} Prix par unité effacé pour :count unité — elle suit de nouveau le tarif du type de chambre.|[0,*] Prix par unité effacé pour :count unités — elles suivent de nouveau le tarif du type de chambre.',
    'priced_summary' => 'Tarification individuelle de :units sur :nights.',
    'units_count' => '{1} :count unité|[0,*] :count unités',
    'nights_count' => '{1} :count nuit|[0,*] :count nuits',
];
