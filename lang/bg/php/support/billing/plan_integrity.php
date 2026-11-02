<?php

// I18N-01 — bg translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Ценовият му модел не е от тези, които механизмът за фактуриране разбира.',
    'per_unit_needs_rate' => 'Планът с цена на единица се нуждае от цена за единица над нула, преди да може да се продава.',
    'flat_needs_price' => 'Планът с фиксирана цена се нуждае от цена над нула, преди да може да се продава.',
    'no_price_id' => 'Все още няма Stripe Price ID – първо създайте Price в Stripe и поставете идентификатора му тук.',
    'placeholder_price_id' => 'Все още е свързан с временен Price ID, който не съществува в Stripe.',
];
