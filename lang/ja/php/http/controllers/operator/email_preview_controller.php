<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/email_preview_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'booking_confirmation_title' => '予約確認',
    'booking_confirmation_status' => '稼働中 · 予約確定時に送信',
    'cancellation_title' => 'キャンセル',
    'cancellation_status' => '稼働中 · 予約キャンセル時に送信',
    'refund_confirmation_title' => '返金確認',
    'refund_confirmation_status' => '稼働中 · 返金処理時に送信',
    'payment_reminder_title' => '支払いリマインダー',
    'payment_reminder_status' => '稼働中 · 毎日実行（emails:send-payment-reminders）',
    'pre_arrival_title' => '到着前の案内',
    'pre_arrival_status' => '稼働中 · 毎日実行（emails:send-pre-arrival、到着48時間前）',
    'review_request_title' => 'クチコミ依頼',
    'review_request_status' => '稼働中 · チェックアウト時に送信',
    'waitlist_notification_title' => 'キャンセル待ちの通知',
    'waitlist_notification_status' => 'テンプレート準備済み · 送信トリガーは準備中（フェーズ4 · キャンセル待ち）',
    'winback_title' => '再訪促進',
    'winback_status' => 'テンプレート準備済み · 送信トリガーは準備中（スケジューラー）',
    'verify_email_title' => 'メールアドレスの確認',
    'verify_email_status' => '稼働中 · 予約に紐づくアカウントの登録時に送信',
    'no_sample_reservation' => 'このワークスペースには、サンプルにできる予約がまだありません。先に予約を作成してください。',
    'no_email_address' => 'アカウントにメールアドレスがないため、テストを送信できません。',
    'test_sent' => ':emailにテストメールを送信しました。',
    'sample_property_fallback' => '施設名',
    'sample_cancellation_reason' => 'ゲストからのキャンセル依頼',
];
