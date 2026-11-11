<?php

// I18N-01 — zh_TW translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => '尚無存檔',
    'state_switched_on' => '啟用中——已開啟',
    'state_not_active' => '未啟用',
    'state_configured' => '已配置',
    'hostinger_hosting' => [
        'name' => 'Hostinger（主機代管）',
        'purpose' => '執行應用程式與其資料庫。',
        'data' => '產品儲存的所有資料——每個帳號、飯店與房客紀錄。',
        'state' => '啟用中——正式主機',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => '在主機前端提供公開網站內容並加以快取。',
        'data' => '經由它的每一個請求：IP 位址、網址、傳輸中的 Cookie。',
        'state' => '啟用中——每次回應都可觀察到',
    ],
    'google' => [
        'name' => 'Google（登入）',
        'purpose' => '使用 Google 帳號登入與註冊。',
        'data' => '當事人的 Google 身分資料：帳號 ID、電子郵件、姓名。',
    ],
    'x' => [
        'name' => 'X（登入）',
        'purpose' => '使用 X 帳號登入與註冊。',
        'data' => '當事人的 X 身分資料：帳號 ID、使用者名稱、姓名。',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger（電子郵件）',
        'purpose' => '從 vyrobel.com 信箱寄出所有交易通知郵件。',
        'data' => '收件人電子郵件地址與郵件內容——房客姓名、訂房編號、日期。',
        'state_active' => '啟用中——目前的郵件程式',
        'state_inactive' => '未啟用——郵件設定為「:mailer」',
    ],
    'stripe' => [
        'name' => 'Stripe（付款與帳務）',
        'purpose' => '訂閱帳務、房客信用卡付款、業主撥款（Connect）。',
        'data' => '帳務聯絡人、金額、訂房編號；業主撥款則為 KYC 所需的身分資料。',
        'state_fake' => '未啟用——已綁定模擬用戶端',
    ],
    'anthropic' => [
        'name' => 'Anthropic（AI 助理）',
        'purpose' => '在 AI 助理中回答問題。',
        'data' => '提出的問題及隨附傳送的上下文。',
        'state_off' => '未啟用——使用模擬用戶端，或沒有金鑰',
    ],
    'channex' => [
        'name' => 'Channex（通路管理系統）',
        'purpose' => '與 OTA 同步房價、可售房數與訂房。',
        'data' => '與通路交換的訂房與房客詳細資料。',
        'state_off' => '未連結',
    ],
];
