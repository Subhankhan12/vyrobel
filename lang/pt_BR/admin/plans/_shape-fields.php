<?php

// I18N-01 — pt_BR translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Modelo de preço',
    'pricing_shape_intro' => 'Um plano <strong>fixo</strong> cobra um único preço, independentemente de quantas unidades a conta opera. Um plano <strong>por unidade</strong> cobra um valor para cada unidade ativa, recontadas a cada período.',
    'shape_label' => 'Modelo',
    'shape_flat_option' => 'Fixo — um preço por período',
    'shape_per_unit_option' => 'Por unidade — valor × unidades ativas',
    'billing_interval_label' => 'Periodicidade de faturamento',
    'interval_monthly_option' => 'Mensal',
    'interval_yearly_option' => 'Anual',
    'price_label' => 'Preço',
    'price_hint' => 'O valor final — com IVA incluído.',
    'unit_rate_label' => 'Valor por unidade',
    'unit_rate_hint' => 'Sem impostos — a Stripe adiciona o imposto do próprio cliente.',
    'currency_label' => 'Moeda',
    'vat_pct_label' => 'IVA % <span style="color: var(--ad-faint); font-weight: 400;">· opcional</span>',
    'stripe_price_heading' => 'Preço Stripe',
    'stripe_price_intro' => 'Crie o preço no painel da Stripe e cole o ID dele aqui — este painel não consegue criá-lo. Um preço por unidade deve ter <strong>cobrança por unidade, comportamento tributário exclusivo</strong>; um fixo, <strong>licenciado, inclusivo</strong>. Consulte <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID do preço Stripe',
    'draft_not_offered' => 'Rascunho — não oferecido aos clientes.',
];
