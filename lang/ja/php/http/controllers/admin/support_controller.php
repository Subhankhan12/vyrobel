<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'その連携はもう存在しません。',
    'unlink_refused_only_way_in' => '拒否しました。これはこのユーザーがログインできる唯一の方法です（パスワードも他の連携サービスもありません）。連携を解除すると、このユーザーは完全にログインできなくなります。',
    'unlinked' => ':providerとの連携を解除しました。読み戻して確認：連携は削除されています。',
    'unlink_not_landed' => '連携の解除が反映されませんでした。連携はまだ残っています。',
    'already_verified' => 'すでに確認済みです。',
    'marked_verified' => '確認済みに設定しました。データベースから読み戻した値：:stored（UTC）。',
    'verify_not_landed' => '書き込みが反映されませんでした。email_verified_atはまだ空です。',
    'already_verified_nothing_to_resend' => 'すでに確認済みです。再送信するものはありません。',
    'verification_discarded' => '未配信 — メールの送信方式が「:mailer」のため、確認メールは破棄されました。誰にも届いていません。別の方法で本人確認を行ってから、「メールアドレスを確認済みに設定」を使用してください。',
    'verification_handed' => '確認メールをメール送信処理（:mailer）に渡しました。',
    'no_reset_link' => 'リセットリンクは作成されませんでした：:status',
    'reset_discarded' => '未配信 — リセットリンクは作成されましたが、メールの送信方式が「:mailer」のため、メールは破棄されました。誰にも届いていません。',
    'reset_handed' => 'パスワードリセットメールをメール送信処理（:mailer）に渡しました。',
    'verification_not_sent_undeliverable' => '未送信 — このアドレスはメールを受信できない（:reason）ため、確認メールはメール送信処理に渡されていません。誰にも届いていません。',
    'reset_not_sent_undeliverable' => '未送信 — このアドレスはメールを受信できない（:reason）ため、リセットリンクはメール送信処理に渡されていません。誰にも届いていません。',
];
