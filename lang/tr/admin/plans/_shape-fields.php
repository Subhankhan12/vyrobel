<?php

// I18N-01 — tr translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Fiyatlandırma modeli',
    'pricing_shape_intro' => '<strong>Sabit</strong> bir paket, hesabın kaç birim işlettiğinden bağımsız olarak tek bir fiyat alır. <strong>Birim başına</strong> bir paket, her aktif birim için bir fiyat alır; birimler her dönem yeniden sayılır.',
    'shape_label' => 'Model',
    'shape_flat_option' => 'Sabit — dönem başına tek fiyat',
    'shape_per_unit_option' => 'Birim başına — fiyat × aktif birim',
    'billing_interval_label' => 'Fatura dönemi',
    'interval_monthly_option' => 'Aylık',
    'interval_yearly_option' => 'Yıllık',
    'price_label' => 'Fiyat',
    'price_hint' => 'Nihai tutar — KDV dahil.',
    'unit_rate_label' => 'Birim başına fiyat',
    'unit_rate_hint' => 'Vergi hariç — müşterinin kendi vergisini Stripe ekler.',
    'currency_label' => 'Para birimi',
    'vat_pct_label' => 'KDV (%) <span style="color: var(--ad-faint); font-weight: 400;">· isteğe bağlı</span>',
    'stripe_price_heading' => 'Stripe Price',
    'stripe_price_intro' => 'Price nesnesini Stripe panelinde oluşturun, ardından kimliğini buraya yapıştırın — bu panel onu oluşturamaz. Birim başına bir Price <strong>birim başına faturalandırma (per-unit), vergi davranışı hariç (exclusive)</strong> olmalıdır; sabit bir Price ise <strong>lisanslı (licensed), dahil (inclusive)</strong>. Bkz. <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe Price kimliği',
    'draft_not_offered' => 'Taslak — müşterilere sunulmuyor.',
];
