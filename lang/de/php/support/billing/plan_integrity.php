<?php

// I18N-01 — de translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Das Preismodell dieses Tarifs wird von der Abrechnung nicht unterstützt.',
    'per_unit_needs_rate' => 'Ein Tarif pro Einheit braucht einen Preis pro Einheit über null, bevor er verkauft werden kann.',
    'flat_needs_price' => 'Ein Pauschaltarif braucht einen Preis über null, bevor er verkauft werden kann.',
    'no_price_id' => 'Der Tarif hat noch keine Stripe-Preis-ID — legen Sie zuerst den Preis in Stripe an und fügen Sie dessen ID hier ein.',
    'placeholder_price_id' => 'Der Tarif ist noch mit einer Platzhalter-Preis-ID verknüpft, die es in Stripe nicht gibt.',
];
