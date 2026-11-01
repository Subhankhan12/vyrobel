<?php

// I18N-01 — es translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Modelo de precio',
    'pricing_shape_intro' => 'Un plan <strong>fijo</strong> cobra un único precio, sin importar cuántas unidades gestione la cuenta. Un plan <strong>por unidad</strong> cobra una tarifa por cada unidad activa, que se vuelve a contar en cada periodo.',
    'shape_label' => 'Modelo',
    'shape_flat_option' => 'Fijo: un precio por periodo',
    'shape_per_unit_option' => 'Por unidad: tarifa × unidades activas',
    'billing_interval_label' => 'Intervalo de facturación',
    'interval_monthly_option' => 'Mensual',
    'interval_yearly_option' => 'Anual',
    'price_label' => 'Precio',
    'price_hint' => 'El importe final, con IVA incluido.',
    'unit_rate_label' => 'Tarifa por unidad',
    'unit_rate_hint' => 'Sin impuestos: Stripe agrega el impuesto que corresponda al cliente.',
    'currency_label' => 'Moneda',
    'vat_pct_label' => '% de IVA <span style="color: var(--ad-faint); font-weight: 400;">· opcional</span>',
    'stripe_price_heading' => 'Precio de Stripe',
    'stripe_price_intro' => 'Cree el precio en el panel de Stripe y pegue aquí su ID; este panel no puede crearlo. Un precio por unidad debe tener <strong>facturación por unidad y comportamiento fiscal exclusivo</strong>; uno fijo, <strong>con licencia e inclusivo</strong>. Consulte <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID del precio de Stripe',
    'draft_not_offered' => 'Borrador: no se ofrece a los clientes.',
];
