<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'ログイン許可はまだ有効にできません。Vyrobelからメールが送信されていないため、許可リンクが届かず、新しいデバイスからログインできなくなります。',
    'login_approval_on' => 'ログイン許可を有効にしました。新しいデバイスは、許可されるまで待機します。',
    'login_approval_off' => 'ログイン許可を無効にしました。',
    'approval_no_longer_waiting' => 'このログインはすでに待機中ではありません。許可または拒否済みか、期限が切れています。',
    'approval_approved' => '許可しました。そのデバイスでログインを完了できます。',
    'approval_denied' => '拒否しました。そのログインは続行できません。心当たりがない場合は、パスワードを変更してください。',
    'login_alerts_on' => 'ログイン通知を有効にしました。新しいデバイスからのログインは、通知ベルに表示されます。',
    'login_alerts_off' => 'ログイン通知を無効にしました。',
    'session_timeout_off' => 'セッションタイムアウトはオフです。これまでどおり、操作がなくても:durationはログインしたままになります。',
    'session_timeout_on' => '操作がない状態が:duration続くと、ログアウトされます。',
];
