<?php

// I18N-01 — zh_CN translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => '暂无存档',
    'state_switched_on' => '已启用——已开启',
    'state_not_active' => '未启用',
    'state_configured' => '已配置',
    'hostinger_hosting' => [
        'name' => 'Hostinger（主机托管）',
        'purpose' => '运行应用程序及其数据库。',
        'data' => '产品存储的全部数据——每个账号、酒店和客人的记录。',
        'state' => '已启用——生产环境主机',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => '在主机前端提供并缓存公开网站。',
        'data' => '经由它的每个请求：IP 地址、URL、传输中的 Cookie。',
        'state' => '已启用——每次响应中均可观察到',
    ],
    'google' => [
        'name' => 'Google（登录）',
        'purpose' => '使用 Google 账号登录和注册。',
        'data' => '此人的 Google 身份信息：账号 ID、邮箱、姓名。',
    ],
    'x' => [
        'name' => 'X（登录）',
        'purpose' => '使用 X 账号登录和注册。',
        'data' => '此人的 X 身份信息：账号 ID、用户名、姓名。',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger（邮件）',
        'purpose' => '通过 vyrobel.com 邮箱发送所有事务性邮件。',
        'data' => '收件人地址和邮件内容——客人姓名、预订号、日期。',
        'state_active' => '已启用——当前邮件发送方式',
        'state_inactive' => '未启用——邮件发送方式为“:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe（付款与计费）',
        'purpose' => '订阅计费、客人银行卡付款、业主打款（Connect）。',
        'data' => '计费联系人、金额、预订号；业主打款则包括用于 KYC 的身份信息。',
        'state_fake' => '未启用——当前绑定的是模拟客户端',
    ],
    'anthropic' => [
        'name' => 'Anthropic（AI 助手）',
        'purpose' => '在 AI 助手中回答问题。',
        'data' => '所提问题及随问题一同发送的上下文。',
        'state_off' => '未启用——使用模拟客户端或未设置 API 密钥',
    ],
    'channex' => [
        'name' => 'Channex（渠道管理器）',
        'purpose' => '与 OTA 同步房价、可售房量和预订。',
        'data' => '与各渠道交换的预订和客人信息。',
        'state_off' => '未连接',
    ],
];
