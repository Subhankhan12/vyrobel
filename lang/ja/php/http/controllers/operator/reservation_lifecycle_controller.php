<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => '確定できるのは仮予約のみです。この予約のステータスは「:status」です。',
    'hold_confirmed' => '仮予約を確定しました。予約は確定済みになりました。',
    'cannot_cancel_closed' => 'その予約はすでに終了しているため、キャンセルできません。',
    'reason_too_long' => 'キャンセル理由が長すぎます。最大255文字です。',
    'cancel_rejected' => '予約をキャンセルできませんでした。予約処理で拒否されました。何も変更されていません。',
    'cancelled_refunded' => '予約をキャンセルし、:amountを返金しました（無料キャンセル期間内）。確保されていた泊はカレンダーに戻りました。',
    'cancelled' => '予約をキャンセルしました。確保されていた泊はカレンダーに戻りました。',
    'cannot_no_show' => 'その予約はノーショーとして記録できません。記録できるのは確定済みの予約のみです。',
    'no_show_rejected' => 'ノーショーとして記録できませんでした。予約処理で拒否されました。何も変更されていません。',
    'marked_no_show' => 'ノーショーとして記録しました。確保されていた泊はカレンダーに戻りました。',
];
