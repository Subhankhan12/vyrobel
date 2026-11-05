<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/owner/owner_rate_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'invalid_dates' => '请选择有效的开始日期和结束日期。',
    'end_before_start' => '结束日期不能早于开始日期。',
    'span_too_long' => '每次最多只能应用一年。',
    'invalid_rate' => '请输入有效的每晚房价，例如：175 或 175.50。',
    'invalid_min_stay' => '最少入住晚数必须是整数晚（1–365），或留空。',
    'invalid_weekend_uplift' => '周末加价必须是 0 到 200 之间的百分比，或留空。',
    'rate_updated' => '{1}已更新 :count 晚的房价，现已生效。|[0,*]已更新 :count 晚的房价，现已生效。',
    'no_rate_plan' => '该房型没有价格计划，因此无法保存房价。',
];
