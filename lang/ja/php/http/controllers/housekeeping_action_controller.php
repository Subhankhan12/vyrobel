<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/housekeeping_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'cannot_mark' => '客室:unitは「:status」のため、「:target」にできません。',
    'marked' => '客室:unitを「:target」にしました。',
    'assigned' => ':nameを客室:unitに割り当てました。',
    'unassigned' => '客室:unitの担当者の割り当てを解除しました。',
    'rooms_unassigned' => '{1}:count室の割り当てを解除しました。|[0,*]:count室の割り当てを解除しました。',
    'nothing_to_undo' => '元に戻す操作はありません。',
    'no_housekeepers' => '割り当てられるハウスキーパーがシフト表にいません。',
    'rooms_assigned' => '{1}:count室を割り当てました · 作業量を均等化しました。|[0,*]:count室を割り当てました · 作業量を均等化しました。',
    'nothing_to_assign' => '割り当てる客室はありません。すべての客室にハウスキーパーが割り当て済みです。',
    'photo_added' => '写真を追加しました。',
    'photo_removed' => '写真を削除しました。',
    'workspace_paused' => 'ワークスペースが一時停止中（読み取り専用）のため、再開するまでハウスキーピングの変更はできません。',
    'marked_in_progress' => '清掃中',
    'marked_clean' => '清掃済み',
    'marked_inspected' => '点検済み',
    'is_pending' => '未清掃',
    'is_in_progress' => '清掃中',
    'is_clean' => '清掃済み',
    'is_inspected' => '点検済み',
    'problem_title_required' => '何が問題か入力してください。',
    'problem_reported' => ':unit — 問題を報告しました。メンテナンスチームへの作業指示が開いています。',
    'second_person_required' => ':unitはあなたが清掃済みにしました。別の担当者が点検する必要があります。',
];
