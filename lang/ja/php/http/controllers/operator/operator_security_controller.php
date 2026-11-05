<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => '有効なIPアドレスまたはCIDRブロックを入力してください（例：203.0.113.4、203.0.113.0/24）。',
    'ip_already_listed' => ':ipはすでに許可リストに登録されています。',
    'allowlist_enabled_auto_added' => 'IP許可リストを有効にしました。締め出されないよう、現在のアドレス（:ip）も追加しました。',
    'ip_added' => ':ipを許可リストに追加しました。',
    'remove_would_lock_out' => ':ipを削除すると締め出されます。現在のアドレス（:current）から管理画面にアクセスできる唯一のルールです。先に自分のアドレス用のルールを追加するか、他のルールを削除してください。',
    'ip_removed_allowlist_off' => ':ipを削除しました。許可リストはオフになり、有効なログイン情報があれば誰でも管理画面にアクセスできます。',
    'ip_removed' => ':ipを許可リストから削除しました。',
    'session_already_ended' => 'このセッションはすでに終了しています。',
    'signed_out_of_device' => ':deviceからログアウトしました。',
    'trusted_device_revoked' => '信頼済みデバイスを無効化しました。',
    'csv_title' => 'Vyrobelアカウントのエクスポート',
    'csv_name' => '名前',
    'csv_email' => 'メールアドレス',
    'csv_locale' => '言語',
    'csv_roles' => 'ロール',
    'csv_activity' => '操作履歴',
    'csv_when' => '日時',
    'csv_action' => '操作',
    'range_this_year' => '今年',
    'range_custom' => '期間を指定',
    'range_all_time' => '全期間',
];
