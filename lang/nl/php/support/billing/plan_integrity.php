<?php

// I18N-01 — nl translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Het factureringssysteem begrijpt deze prijsstructuur niet.',
    'per_unit_needs_rate' => 'Een pakket per eenheid heeft een tarief per eenheid boven nul nodig voordat het kan worden verkocht.',
    'flat_needs_price' => 'Een pakket met een vaste prijs heeft een prijs boven nul nodig voordat het kan worden verkocht.',
    'no_price_id' => 'Er is nog geen Stripe Price-id — maak eerst de Price aan in Stripe en plak de id hier.',
    'placeholder_price_id' => 'Het is nog gekoppeld aan een tijdelijke Price-id, die niet bestaat in Stripe.',
];
