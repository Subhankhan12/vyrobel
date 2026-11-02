<?php

// I18N-01 — ro translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Structura sa de preț nu este una pe care motorul de facturare o înțelege.',
    'per_unit_needs_rate' => 'Un plan per unitate are nevoie de un preț pe unitate mai mare decât zero înainte de a putea fi vândut.',
    'flat_needs_price' => 'Un plan cu preț fix are nevoie de un preț mai mare decât zero înainte de a putea fi vândut.',
    'no_price_id' => 'Încă nu are un ID de Price Stripe — creați mai întâi Price-ul în Stripe, apoi lipiți aici ID-ul acestuia.',
    'placeholder_price_id' => 'Este încă legat de un ID de Price provizoriu, care nu există în Stripe.',
];
