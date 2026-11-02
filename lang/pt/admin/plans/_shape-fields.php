<?php

// I18N-01 — pt translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Modelo de preço',
    'pricing_shape_intro' => 'Um plano <strong>fixo</strong> cobra um único preço, independentemente do número de unidades da conta. Um plano <strong>por unidade</strong> cobra um preço por cada unidade ativa, com as unidades recontadas em cada período.',
    'shape_label' => 'Modelo',
    'shape_flat_option' => 'Fixo — um preço por período',
    'shape_per_unit_option' => 'Por unidade — preço × unidades ativas',
    'billing_interval_label' => 'Periodicidade de faturação',
    'interval_monthly_option' => 'Mensal',
    'interval_yearly_option' => 'Anual',
    'price_label' => 'Preço',
    'price_hint' => 'O valor final — com IVA incluído.',
    'unit_rate_label' => 'Preço por unidade',
    'unit_rate_hint' => 'Sem impostos — a Stripe acrescenta o imposto do próprio cliente.',
    'currency_label' => 'Moeda',
    'vat_pct_label' => 'IVA % <span style="color: var(--ad-faint); font-weight: 400;">· opcional</span>',
    'stripe_price_heading' => 'Preço Stripe',
    'stripe_price_intro' => 'Crie o preço no painel da Stripe e cole aqui o seu ID — este painel não o consegue criar. Um preço por unidade tem de ter <strong>faturação por unidade, comportamento fiscal exclusivo</strong>; um preço fixo, <strong>licenciado, inclusivo</strong>. Consulte <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID do preço Stripe',
    'draft_not_offered' => 'Rascunho — não disponível para clientes.',
];
