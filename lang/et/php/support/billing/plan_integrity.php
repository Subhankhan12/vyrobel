<?php

// I18N-01 — et translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'Selle plaani hinnastamise viis ei ole selline, millest arveldusmootor aru saaks.',
    'per_unit_needs_rate' => 'Üksusepõhisel plaanil peab enne müügiletulekut olema üksuse hind suurem kui null.',
    'flat_needs_price' => 'Ühtse hinnaga plaanil peab enne müügiletulekut olema hind suurem kui null.',
    'no_price_id' => 'Sellel plaanil pole veel Stripe’i hinna ID-d — looge hind (Price) Stripe’is ja kleepige selle ID esmalt siia.',
    'placeholder_price_id' => 'See plaan on endiselt seotud kohatäitena kasutatava hinna ID-ga, mida Stripe’is ei ole.',
];
