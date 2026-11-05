<?php

// I18N-01 — zh_CN translation of lang/en/admin/integrations/edit.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'subtitle_configured' => '已配置主凭据（已加密）。仅在需要轮换时，才在下方输入新值。',
    'subtitle_none' => '尚无凭据。请为 :channel 添加平台主凭据。',
    'back_label' => '所有集成',
    'master_credential' => '主凭据',
    'api_key' => 'API 密钥',
    'api_key_placeholder_configured' => '••••••••（输入新值以轮换）',
    'api_key_placeholder' => '请输入 API 密钥',
    'api_secret' => 'API 私钥 <span style="text-transform: none; letter-spacing: 0; color: var(--ad-faint);">· 可选</span>',
    'api_secret_placeholder_configured' => '••••••••（留空则保持不变）',
    'api_secret_placeholder' => '请输入 API 私钥',
    'enabled' => '启用',
    'stored_encrypted' => '🔒 静态加密存储。私钥不会再次显示——只能替换。',
    'reason_placeholder' => '为什么要设置或轮换此凭据？',
    'save_credential' => '保存凭据',
    'test_reason_placeholder' => '测试原因',
    'test_reason_aria' => '原因',
    'test_connection' => '测试连接',
    'test_hint' => '仅验证凭据是否存在；连接 :channel 账号后才会运行实时测试。',
];
