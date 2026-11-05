<?php

// I18N-01 — et translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Hinnastamise viis',
    'pricing_shape_intro' => '<strong>Ühtse hinnaga</strong> plaanis on üks hind, olenemata sellest, mitu majutusüksust kontol on. <strong>Üksusepõhises</strong> plaanis makstakse hinda iga aktiivse majutusüksuse eest ja nende arv loetakse igal perioodil uuesti.',
    'shape_label' => 'Hinnastus',
    'shape_flat_option' => 'Ühtne hind — üks hind perioodi kohta',
    'shape_per_unit_option' => 'Üksusepõhine — hind × aktiivsed üksused',
    'billing_interval_label' => 'Arveldusperiood',
    'interval_monthly_option' => 'Igakuine',
    'interval_yearly_option' => 'Iga-aastane',
    'price_label' => 'Hind',
    'price_hint' => 'Lõppsumma — sisaldab käibemaksu.',
    'unit_rate_label' => 'Hind üksuse kohta',
    'unit_rate_hint' => 'Maksudeta — Stripe lisab kliendi enda maksu.',
    'currency_label' => 'Valuuta',
    'vat_pct_label' => 'KM % <span style="color: var(--ad-faint); font-weight: 400;">· valikuline</span>',
    'stripe_price_heading' => 'Stripe’i hind',
    'stripe_price_intro' => 'Looge hind (Price) Stripe’i töölaual ja kleepige selle ID siia — see paneel ei saa seda luua. Üksusepõhisel hinnal peavad Stripe’is olema seaded <strong>per-unit billing, tax behaviour exclusive</strong>; ühtsel hinnal <strong>licensed, inclusive</strong>. Vt <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe’i hinna ID',
    'draft_not_offered' => 'Mustand — klientidele ei pakuta.',
];
