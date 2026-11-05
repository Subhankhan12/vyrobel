<?php

// I18N-01 — ja translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => '料金体系が、請求エンジンで処理できない形式です。',
    'per_unit_needs_rate' => '客室単位のプランは、販売する前に0より大きい客室単価を設定する必要があります。',
    'flat_needs_price' => '定額プランは、販売する前に0より大きい価格を設定する必要があります。',
    'no_price_id' => 'Stripe価格IDがまだありません。先にStripeで価格を作成し、そのIDをここに貼り付けてください。',
    'placeholder_price_id' => 'まだ仮の価格IDに紐付けられていますが、そのIDはStripeに存在しません。',
];
