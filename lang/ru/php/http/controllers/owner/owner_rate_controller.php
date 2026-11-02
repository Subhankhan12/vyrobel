<?php

// I18N-01 — ru translation of lang/en/php/http/controllers/owner/owner_rate_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'invalid_dates' => 'Выберите корректные даты начала и окончания.',
    'end_before_start' => 'Дата окончания не может быть раньше даты начала.',
    'span_too_long' => 'Применяйте изменения не более чем на год за раз.',
    'invalid_rate' => 'Введите корректную цену за ночь, напр. 175 или 175,50.',
    'invalid_min_stay' => 'Минимальный срок должен быть целым числом ночей (1–365) или оставаться пустым.',
    'invalid_weekend_uplift' => 'Наценка на выходные должна быть в процентах от 0 до 200 или оставаться пустой.',
    'rate_updated' => '{1}Цена обновлена на :count ночь. Она уже действует.|Цена обновлена на :count ночи. Она уже действует.|Цена обновлена на :count ночей. Она уже действует.',
    'no_rate_plan' => 'У этой категории номера нет тарифа, поэтому цену не удалось сохранить.',
];
