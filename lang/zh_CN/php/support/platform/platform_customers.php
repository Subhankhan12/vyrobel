<?php

// I18N-01 — zh_CN translation of lang/en/php/support/platform/platform_customers.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'bucket_unverified' => '超过 7 天未验证',
    'bucket_unverified_definition' => '创建已超过 7 天，且其用户均未确认邮箱地址。',
    'bucket_never_signed_in' => '从未登录',
    'bucket_never_signed_in_definition' => '其用户均从未完成过登录。⚠️ 注册时的会话本身不算登录，因此这表示“注册后再也没有回来”。',
    'bucket_via_x' => '通过 X 创建',
    'bucket_via_x_definition' => '通过 X 注册。X 不像 Google 那样验证邮箱地址——这是已接受的抢注风险。此处仅列出以供审查，绝不会自动处理。',
    'bucket_abandoned' => '已放弃的注册（超过 30 天）',
    'bucket_abandoned_definition' => '创建已超过 30 天，无人确认过邮箱地址，也无人登录过。打开其中一个将其冻结，然后用您的密码将其删除。系统绝不会自动移除任何内容。',
    'status_active' => '正常',
    'status_trialing' => '免费试用中',
    'status_suspended' => '已冻结',
    'status_cancelled' => '已取消',
    'status_archived' => '已归档',
    'subscription_trialing' => '免费试用中',
    'subscription_active' => '有效——计费中',
    'subscription_past_due' => '逾期未付',
    'subscription_canceled' => '已取消订阅',
    'subscription_incomplete' => '未完成',
    'subscription_paused' => '已暂停',
    'plan_on_free_trial' => ':plan · 免费试用',
    'plan_complimentary' => ':plan · 免费赠送',
];
