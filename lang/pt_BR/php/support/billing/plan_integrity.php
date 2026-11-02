<?php

// I18N-01 — pt_BR translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'O sistema de faturamento não reconhece o modelo de preço deste plano.',
    'per_unit_needs_rate' => 'Um plano por unidade precisa de um valor por unidade acima de zero antes de poder ser vendido.',
    'flat_needs_price' => 'Um plano fixo precisa de um preço acima de zero antes de poder ser vendido.',
    'no_price_id' => 'Ele ainda não tem ID do preço Stripe — crie o preço na Stripe e cole o ID dele aqui primeiro.',
    'placeholder_price_id' => 'Ele ainda está vinculado a um ID de preço provisório, que não existe na Stripe.',
];
