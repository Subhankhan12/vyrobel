<?php

// I18N-01 — it translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Il suo modello di prezzo non è tra quelli gestiti dal sistema di fatturazione.',
    'per_unit_needs_rate' => 'Un piano per unità richiede un prezzo per unità superiore a zero prima di poter essere venduto.',
    'flat_needs_price' => 'Un piano forfettario richiede un prezzo superiore a zero prima di poter essere venduto.',
    'no_price_id' => 'Non ha ancora un ID del prezzo Stripe: creare prima il prezzo in Stripe e incollarne qui l’ID.',
    'placeholder_price_id' => 'È ancora collegato a un ID del prezzo provvisorio, che non esiste in Stripe.',
];
