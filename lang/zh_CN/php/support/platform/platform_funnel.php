<?php

// I18N-01 — zh_CN translation of lang/en/php/support/platform/platform_funnel.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'stage_signed_up' => '已注册',
    'stage_verified' => '已验证邮箱（进入 /app 的前提）',
    'stage_first_reservation' => '首个预订',
    'stage_first_check_in' => '首次入住',
    'stage_paid' => '已付款（由 Stripe 扣款）',
    'unmeasured_visited' => '访问网站',
    'unmeasured_visited_why' => '按设计不记录任何分析数据或访问日志。',
    'unmeasured_started_form' => '开始填写注册表单',
    'unmeasured_started_form_why' => '表单提交前不写入任何数据。',
    'unmeasured_reached_app' => '进入 /app',
    'unmeasured_reached_app_why' => '不记录页面浏览。“已验证邮箱”是最接近的已记录步骤——每个 /app 页面都要求先完成这一步。',
];
