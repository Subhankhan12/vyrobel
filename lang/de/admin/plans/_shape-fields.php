<?php

// I18N-01 — de translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Preismodell',
    'pricing_shape_intro' => 'Ein <strong>pauschaler</strong> Tarif berechnet einen Preis, unabhängig davon, wie viele Einheiten das Konto betreibt. Ein Tarif <strong>pro Einheit</strong> berechnet einen Preis für jede aktive Einheit, in jedem Zeitraum neu gezählt.',
    'shape_label' => 'Modell',
    'shape_flat_option' => 'Pauschal — ein Preis pro Zeitraum',
    'shape_per_unit_option' => 'Pro Einheit — Preis × aktive Einheiten',
    'billing_interval_label' => 'Abrechnungsintervall',
    'interval_monthly_option' => 'Monatlich',
    'interval_yearly_option' => 'Jährlich',
    'price_label' => 'Preis',
    'price_hint' => 'Der Endbetrag — inkl. MwSt.',
    'unit_rate_label' => 'Preis pro Einheit',
    'unit_rate_hint' => 'Vor Steuern — Stripe fügt die Steuer des Kunden hinzu.',
    'currency_label' => 'Währung',
    'vat_pct_label' => 'MwSt.-Satz in % <span style="color: var(--ad-faint); font-weight: 400;">· optional</span>',
    'stripe_price_heading' => 'Stripe-Preis',
    'stripe_price_intro' => 'Legen Sie den Preis im Stripe-Dashboard an und fügen Sie dann hier seine ID ein — diese Seite kann ihn nicht anlegen. Ein Preis pro Einheit muss <strong>Abrechnung pro Einheit, Steuerverhalten exklusiv</strong> sein; ein pauschaler <strong>lizenziert, inklusiv</strong>. Siehe <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe-Preis-ID',
    'draft_not_offered' => 'Entwurf — wird Kunden nicht angeboten.',
];
