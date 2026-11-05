<?php

// I18N-01 — ja translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => '緊急停止スイッチ',
    'subtitle' => 'デプロイなしで、新規登録や特定の連携サービスによるログインを停止します。切り替えはすべて理由とともに監査ログに記録されます。',
    'explainer' => 'ここで誰かがオフにしない限り、すべてのスイッチは<strong>オン</strong>です。デプロイ直後も同様です。このコンソールに影響するスイッチはありません。連携サービスによる<strong>ログイン</strong>をオフにすると、その連携サービスが唯一のログイン手段であるユーザーはログインできなくなるため、これらのスイッチでは該当件数を表示し、2回確認します。',
    'state_on' => 'オン',
    'state_off' => 'オフ',
    'last_changed_by' => ':time（UTC）に:nameが最終変更しました。',
    'last_changed' => '最終変更：:time（UTC）。',
    'never_changed' => '変更履歴なし — 初期設定でオンです。',
    'no_account_relies' => '現在、この連携サービスだけでログインしているアカウントはありません。どのアカウントにもパスワードまたは別のログイン手段があります。',
    'would_lock_out' => '{1}これをオフにすると、<strong>:count</strong>件のアカウントがログインできなくなります。パスワードがなく、この連携サービスが唯一のログイン手段です。|[0,*]これをオフにすると、<strong>:count</strong>件のアカウントがログインできなくなります。パスワードがなく、この連携サービスが唯一のログイン手段です。',
    'reason' => '理由',
    'why_turn_off' => 'オフにする理由は？',
    'why_turn_on' => 'オンにする理由は？',
    'confirm_lockout_zero' => 'この方法でのみログインしているユーザーは、再びオンにするまでログインできなくなることを理解しました。',
    'confirm_lockout' => '{1}再びオンにするまで、:count件のアカウントがログインできなくなることを理解しました。|[0,*]再びオンにするまで、:count件のアカウントがログインできなくなることを理解しました。',
    'turn_off' => 'オフにする',
    'turn_on' => 'オンにする',
];
