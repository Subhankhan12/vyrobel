<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => '再発行できるのは、承諾待ちの招待のみです。',
    'last_owner_role' => 'この管理者は最後の有効な「オーナー」です。先に別の管理者を「オーナー」にしてください。',
    'role_changed' => ':nameのロールを:roleに変更しました。',
    'cannot_revoke_self' => '自分のアクセスは無効化できません。別の「オーナー」に依頼してください。',
    'last_owner_revoke' => 'この管理者は最後の有効な「オーナー」のため、アクセスを無効化できません。',
    'access_revoked' => '{1}:nameのアクセスを無効化し、セッション:count件を終了しました。|[0,*]:nameのアクセスを無効化し、セッション:count件を終了しました。',
    'only_revoked_restored' => '再有効化できるのは、アクセスが無効化された管理者のみです。',
    'access_restored' => ':nameのアクセスを再有効化しました。',
    'ended_own_other_sessions' => '{1}他のセッション:count件を終了しました。このセッションはログインしたままです。|[0,*]他のセッション:count件を終了しました。このセッションはログインしたままです。',
    'ended_sessions_for' => '{1}:nameのセッション:count件を終了しました。|[0,*]:nameのセッション:count件を終了しました。',
];
