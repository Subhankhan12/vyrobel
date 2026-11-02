<?php

// I18N-01 — pl translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Model cenowy',
    'pricing_shape_intro' => 'Plan <strong>ryczałtowy</strong> nalicza jedną cenę niezależnie od liczby jednostek na koncie. Plan <strong>za jednostkę</strong> nalicza cenę za każdą aktywną jednostkę, liczoną od nowa w każdym okresie.',
    'shape_label' => 'Model',
    'shape_flat_option' => 'Ryczałt – jedna cena za okres',
    'shape_per_unit_option' => 'Za jednostkę – cena × aktywne jednostki',
    'billing_interval_label' => 'Okres rozliczeniowy',
    'interval_monthly_option' => 'Miesięczny',
    'interval_yearly_option' => 'Roczny',
    'price_label' => 'Cena',
    'price_hint' => 'Kwota końcowa – z VAT.',
    'unit_rate_label' => 'Cena za jednostkę',
    'unit_rate_hint' => 'Bez podatku – Stripe dolicza podatek właściwy dla klienta.',
    'currency_label' => 'Waluta',
    'vat_pct_label' => 'VAT % <span style="color: var(--ad-faint); font-weight: 400;">· opcjonalnie</span>',
    'stripe_price_heading' => 'Cena w Stripe',
    'stripe_price_intro' => 'Utwórz cenę (Price) w panelu Stripe, a następnie wklej tutaj jej ID – ten panel nie może jej utworzyć. Cena za jednostkę musi mieć ustawienia <strong>rozliczanie za jednostkę (per-unit), podatek doliczany (exclusive)</strong>; cena ryczałtowa – <strong>licencja (licensed), podatek wliczony (inclusive)</strong>. Zob. <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID ceny w Stripe',
    'draft_not_offered' => 'Wersja robocza – plan niedostępny dla klientów.',
];
