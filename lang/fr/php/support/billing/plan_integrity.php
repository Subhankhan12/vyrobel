<?php

// I18N-01 — fr translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Son mode de tarification n’est pas reconnu par le moteur de facturation.',
    'per_unit_needs_rate' => 'Une formule par unité doit avoir un tarif unitaire supérieur à zéro avant de pouvoir être vendue.',
    'flat_needs_price' => 'Une formule forfaitaire doit avoir un prix supérieur à zéro avant de pouvoir être vendue.',
    'no_price_id' => 'Elle n’a pas encore d’ID de prix Stripe — créez d’abord le prix dans Stripe, puis collez son ID ici.',
    'placeholder_price_id' => 'Elle est encore liée à un ID de prix provisoire, qui n’existe pas dans Stripe.',
];
