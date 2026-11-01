<?php

// I18N-01 — es translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'El motor de facturación no reconoce su modelo de precio.',
    'per_unit_needs_rate' => 'Un plan por unidad necesita una tarifa por unidad superior a cero antes de poder venderse.',
    'flat_needs_price' => 'Un plan de precio fijo necesita un precio superior a cero antes de poder venderse.',
    'no_price_id' => 'Todavía no tiene ID del precio de Stripe: cree primero el precio en Stripe y pegue aquí su ID.',
    'placeholder_price_id' => 'Todavía está vinculado a un ID de precio provisional, que no existe en Stripe.',
];
