<?php

// I18N-01 — ru translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Такую схему цен система оплаты не поддерживает.',
    'per_unit_needs_rate' => 'План с оплатой за номер можно продавать только при цене за номер выше нуля.',
    'flat_needs_price' => 'Фиксированный план можно продавать только при цене выше нуля.',
    'no_price_id' => 'У него ещё нет Price ID в Stripe — сначала создайте Price в Stripe и вставьте сюда его ID.',
    'placeholder_price_id' => 'Он всё ещё привязан к Price ID-заглушке, которого нет в Stripe.',
];
