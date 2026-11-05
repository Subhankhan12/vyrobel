<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => '手数料をパーセントで入力してください。',
    'commission_range' => '手数料は0〜100の数値で入力してください。',
    'commission_unchanged' => '変更はありません。:nameの手数料は:dateからすでに:rateです。',
    'commission_from_today' => ':nameの手数料は本日から:rateです。有効な契約がありませんでした。',
    'commission_waiting_cancelled' => ':nameの手数料は:rateのままです。:dateに予定されていた変更は行われません。',
    'commission_from_date' => ':nameの手数料は:dateから:rateになります。今後計算される予約に適用され、計算済みの予約は元の手数料のままです。',
    'payouts_paused' => ':nameへの送金を一時停止しました。再開するまで、このオーナーへの送金は行われません。',
    'payouts_resumed' => ':nameへの送金を再開しました。',
    'period_start_required' => '期間の初日を選択してください。',
    'period_end_required' => '期間の最終日を選択してください。',
    'period_end_after_start' => '期間の終了日は、開始日と同じ日かそれ以降にしてください。',
    'period_not_closed' => 'この期間を締められませんでした：:reason最後に締めた期間より後に始まる期間を選択してください。',
    'period_closed' => '期間を締めました。収支報告書番号:numberを支払額:amountで確定しました。ゲストから未回収の売上は計上されず、後の収支報告書に反映されます。',
    'note_required' => 'メモを入力してください。',
    'note_max' => 'メモは2,000文字以内で入力してください。',
    'note_added' => 'メモを追加しました。',
    'remove_password_required' => 'このオーナーをアーカイブするには、パスワードを入力してください。',
    'password_wrong' => 'パスワードが正しくありません。ログイン時のパスワードを入力してください。',
    'confirm_name_required' => '確認のため、オーナー名を入力してください。',
    'confirm_name_mismatch' => '入力した名前が一致しません。オーナーはアーカイブされていません。',
    'owner_removed' => ':nameをアーカイブしました。客室の割り当ては解除され、収支報告書と送金はすべて会計用に保持されます。',
    'workspace_paused_until' => 'ワークスペースが一時停止中のため、:dateまでオーナーの変更は読み取り専用です。',
    'workspace_paused_for_now' => 'ワークスペースが一時停止中のため、現在、オーナーの変更は読み取り専用です。',
];
