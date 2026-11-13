<?php

// I18N-01 — zh_TW translation of lang/en/php/support/platform/platform_customers.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'bucket_unverified' => '超過 7 天未驗證',
    'bucket_unverified_definition' => '建立已超過 7 天，且其使用者皆尚未確認電子郵件地址。',
    'bucket_never_signed_in' => '從未登入',
    'bucket_never_signed_in_definition' => '其使用者皆從未完成登入。⚠️ 註冊當下的工作階段本身不算登入，因此這代表「註冊後再也沒回來過」。',
    'bucket_via_x' => '透過 X 建立',
    'bucket_via_x_definition' => '透過 X 註冊。X 不像 Google 那樣驗證電子郵件地址——這是已接受的搶註風險。僅列出供審查，絕不自動處理。',
    'bucket_abandoned' => '已放棄的註冊（超過 30 天）',
    'bucket_abandoned_definition' => '建立已超過 30 天，沒有人確認過電子郵件地址，也沒有人登入過。開啟其中一個將其停權，再使用您的密碼將其刪除。系統絕不會自動移除任何內容。',
    'status_active' => '正常',
    'status_trialing' => '免費試用中',
    'status_suspended' => '已停權',
    'status_cancelled' => '已取消',
    'status_archived' => '已封存',
    'subscription_trialing' => '免費試用中',
    'subscription_active' => '有效——已計費',
    'subscription_past_due' => '逾期未付',
    'subscription_canceled' => '已終止',
    'subscription_incomplete' => '未完成',
    'subscription_paused' => '已暫停',
    'plan_on_free_trial' => ':plan · 免費試用',
    'plan_complimentary' => ':plan · 免費贈送',
];
