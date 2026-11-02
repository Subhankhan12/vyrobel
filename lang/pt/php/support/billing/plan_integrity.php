<?php

// I18N-01 — pt translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'O seu modelo de preço não é reconhecido pelo motor de faturação.',
    'per_unit_needs_rate' => 'Um plano por unidade precisa de um preço por unidade superior a zero antes de poder ser vendido.',
    'flat_needs_price' => 'Um plano de preço fixo precisa de um preço superior a zero antes de poder ser vendido.',
    'no_price_id' => 'Ainda não tem ID do preço Stripe — crie primeiro o preço na Stripe e cole aqui o respetivo ID.',
    'placeholder_price_id' => 'Ainda está associado a um ID de preço provisório, que não existe na Stripe.',
];
