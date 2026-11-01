<?php

// I18N-01 — fr translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Structure tarifaire',
    'pricing_shape_intro' => 'Une formule <strong>forfaitaire</strong> facture un prix unique quel que soit le nombre d’unités exploitées par le compte. Une formule <strong>par unité</strong> facture un tarif pour chaque unité active, les unités étant recomptées à chaque période.',
    'shape_label' => 'Structure',
    'shape_flat_option' => 'Forfaitaire — un prix par période',
    'shape_per_unit_option' => 'Par unité — tarif × unités actives',
    'billing_interval_label' => 'Périodicité de facturation',
    'interval_monthly_option' => 'Mensuel',
    'interval_yearly_option' => 'Annuel',
    'price_label' => 'Prix',
    'price_hint' => 'Le montant final — TVA incluse.',
    'unit_rate_label' => 'Tarif par unité',
    'unit_rate_hint' => 'Hors taxes — Stripe ajoute la taxe propre à l’organisation cliente.',
    'currency_label' => 'Devise',
    'vat_pct_label' => 'TVA (%) <span style="color: var(--ad-faint); font-weight: 400;">· facultatif</span>',
    'stripe_price_heading' => 'Prix Stripe',
    'stripe_price_intro' => 'Créez le prix dans le tableau de bord Stripe, puis collez son identifiant ici — ce panneau ne peut pas le créer. Un prix par unité doit être en <strong>facturation par unité, comportement fiscal exclusif</strong> ; un prix forfaitaire en <strong>licence, inclusif</strong>. Voir <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID du prix Stripe',
    'draft_not_offered' => 'Brouillon — non proposée aux organisations clientes.',
];
