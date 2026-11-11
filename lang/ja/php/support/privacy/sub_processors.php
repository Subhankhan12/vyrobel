<?php

// I18N-01 — ja translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => '契約書の保管なし',
    'state_switched_on' => '有効 — オンになっています',
    'state_not_active' => '有効ではありません',
    'state_configured' => '設定済み',
    'hostinger_hosting' => [
        'name' => 'Hostinger（ホスティング）',
        'purpose' => 'アプリケーションとそのデータベースを稼働させます。',
        'data' => '製品が保存するすべてのデータ（すべてのアカウント、ホテル、ゲストの記録）。',
        'state' => '有効 — 本番サーバー',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'ホスティングサーバーの前段で、公開サイトを配信し、キャッシュします。',
        'data' => '通過するすべてのリクエスト：IPアドレス、URL、転送中のCookie。',
        'state' => '有効 — すべてのレスポンスで確認済み',
    ],
    'google' => [
        'name' => 'Google（ログイン）',
        'purpose' => 'Googleアカウントでのログインと新規登録。',
        'data' => '本人のGoogleアカウント情報：アカウントID、メールアドレス、名前。',
    ],
    'x' => [
        'name' => 'X（ログイン）',
        'purpose' => 'Xアカウントでのログインと新規登録。',
        'data' => '本人のXアカウント情報：アカウントID、ハンドル名、名前。',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger（メール）',
        'purpose' => 'vyrobel.comのメールボックスから、すべてのトランザクションメールを配信します。',
        'data' => '宛先アドレスとメッセージの内容（ゲスト名、予約番号、日付）。',
        'state_active' => '有効 — 使用中のメーラー',
        'state_inactive' => '有効ではありません — メールの送信方式は「:mailer」です',
    ],
    'stripe' => [
        'name' => 'Stripe（決済・請求）',
        'purpose' => 'サブスクリプションの請求、ゲストのカード決済、オーナーへの送金（Connect）。',
        'data' => '請求先の連絡先、金額、予約番号。オーナーへの送金では、KYCのための本人確認情報。',
        'state_fake' => '有効ではありません — フェイククライアントが割り当てられています',
    ],
    'anthropic' => [
        'name' => 'Anthropic（AIアシスタント）',
        'purpose' => 'AIアシスタントで質問に回答します。',
        'data' => '質問と、それとあわせて送信されるコンテキスト。',
        'state_off' => '有効ではありません — フェイククライアントまたはキー未設定',
    ],
    'channex' => [
        'name' => 'Channex（サイトコントローラー）',
        'purpose' => '料金、空室状況、予約をOTAと同期します。',
        'data' => 'チャネルとやり取りされる予約とゲストの詳細情報。',
        'state_off' => '未接続',
    ],
];
