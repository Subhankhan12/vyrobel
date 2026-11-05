<?php

// I18N-01 — id translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Model harga',
    'pricing_shape_intro' => 'Paket <strong>tetap</strong> menagih satu harga berapa pun jumlah unit yang dijalankan akun. Paket <strong>per unit</strong> menagih tarif untuk setiap unit aktif, dihitung ulang setiap periode.',
    'shape_label' => 'Model',
    'shape_flat_option' => 'Tetap — satu harga per periode',
    'shape_per_unit_option' => 'Per unit — tarif × unit aktif',
    'billing_interval_label' => 'Interval penagihan',
    'interval_monthly_option' => 'Bulanan',
    'interval_yearly_option' => 'Tahunan',
    'price_label' => 'Harga',
    'price_hint' => 'Jumlah akhir — sudah termasuk PPN.',
    'unit_rate_label' => 'Tarif per unit',
    'unit_rate_hint' => 'Sebelum pajak — Stripe menambahkan pajak milik pelanggan sendiri.',
    'currency_label' => 'Mata uang',
    'vat_pct_label' => 'PPN % <span style="color: var(--ad-faint); font-weight: 400;">· opsional</span>',
    'stripe_price_heading' => 'Harga Stripe',
    'stripe_price_intro' => 'Buat Harga di dasbor Stripe, lalu tempel ID-nya di sini — panel ini tidak dapat membuatnya. Harga per unit harus <strong>penagihan per unit, perilaku pajak eksklusif</strong>; harga tetap harus <strong>berlisensi, inklusif</strong>. Lihat <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID Harga Stripe',
    'draft_not_offered' => 'Draf — tidak ditawarkan ke pelanggan.',
];
