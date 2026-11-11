<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'カード決済はStripe（テストモード）で処理され、本番キーへの切り替え後にご利用いただけます。今すぐフロントで入金を受けるには、現金または銀行振込を記録してください。',
    'payment_amount_invalid' => '0より大きい入金額を入力してください。',
    'payment_recorded' => '入金を記録しました。',
    'charge_amount_invalid' => '0より大きい請求額を入力してください。',
    'charge_added' => '請求項目を追加しました。',
    'refund_only_settled' => '返金できるのは決済済みの支払いのみです。',
    'already_fully_refunded' => 'この支払いはすでに全額返金済みです。',
    'refund_amount_invalid' => '0より大きい返金額を入力してください。',
    'refund_exceeds' => '返金を拒否しました。この支払いで返金できる残りは:amount :currencyのみです。',
    'refund_issued' => '返金しました。',
    'billed_to_account' => ':accountに請求しました。支払われるまで残額は未払いのままです。',
    'returned_to_guest' => 'ゲストのフォリオに戻しました。',
    'hold_released' => '与信を解放しました。ゲストのカードの利用枠が元に戻り、請求は発生していません。',
];
