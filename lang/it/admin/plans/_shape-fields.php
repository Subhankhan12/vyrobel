<?php

// I18N-01 — it translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Modello di prezzo',
    'pricing_shape_intro' => 'Un piano <strong>forfettario</strong> addebita un unico prezzo, indipendentemente dal numero di unità gestite dall’account. Un piano <strong>per unità</strong> addebita una tariffa per ogni unità attiva, ricalcolata a ogni periodo.',
    'shape_label' => 'Modello',
    'shape_flat_option' => 'Forfettario — un prezzo per periodo',
    'shape_per_unit_option' => 'Per unità — tariffa × unità attive',
    'billing_interval_label' => 'Periodicità di fatturazione',
    'interval_monthly_option' => 'Mensile',
    'interval_yearly_option' => 'Annuale',
    'price_label' => 'Prezzo',
    'price_hint' => 'L’importo finale — IVA inclusa.',
    'unit_rate_label' => 'Tariffa per unità',
    'unit_rate_hint' => 'Al netto delle imposte — Stripe aggiunge le imposte del cliente.',
    'currency_label' => 'Valuta',
    'vat_pct_label' => 'IVA % <span style="color: var(--ad-faint); font-weight: 400;">· facoltativa</span>',
    'stripe_price_heading' => 'Prezzo Stripe',
    'stripe_price_intro' => 'Creare il prezzo nella dashboard di Stripe, poi incollarne qui l’ID — questo pannello non può crearlo. Un prezzo per unità deve avere <strong>fatturazione per unità, comportamento fiscale esclusivo</strong>; uno forfettario <strong>con licenza, inclusivo</strong>. Vedere <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID del prezzo Stripe',
    'draft_not_offered' => 'Bozza — non offerto ai clienti.',
];
