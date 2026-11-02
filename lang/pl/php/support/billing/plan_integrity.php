<?php

// I18N-01 — pl translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Jego model cenowy nie jest obsługiwany przez mechanizm rozliczeń.',
    'per_unit_needs_rate' => 'Plan za jednostkę musi mieć cenę za jednostkę większą od zera, zanim będzie można go sprzedawać.',
    'flat_needs_price' => 'Plan ryczałtowy musi mieć cenę większą od zera, zanim będzie można go sprzedawać.',
    'no_price_id' => 'Nie ma jeszcze identyfikatora Price ze Stripe – najpierw utwórz Price w Stripe i wklej tutaj jego identyfikator.',
    'placeholder_price_id' => 'Nadal jest powiązany z zastępczym identyfikatorem Price, który nie istnieje w Stripe.',
];
