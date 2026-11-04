<?php

// I18N-01 — sr translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Модел цена овог плана није један од оних које модул за наплату разуме.',
    'per_unit_needs_rate' => 'План по јединици мора имати цену по јединици већу од нуле пре него што може да се понуди на продају.',
    'flat_needs_price' => 'Паушални план мора имати цену већу од нуле пре него што може да се понуди на продају.',
    'no_price_id' => 'Овај план још нема идентификатор цене (Stripe Price) – прво направите цену у систему Stripe и налепите њен идентификатор овде.',
    'placeholder_price_id' => 'Овај план је и даље повезан са привременим идентификатором цене (Stripe Price) који не постоји у систему Stripe.',
];
