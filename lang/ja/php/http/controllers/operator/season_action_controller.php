<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => '料金プランを選択してください。',
    'name_required' => 'シーズン名を入力してください。',
    'name_too_long' => 'シーズン名は80文字以内にしてください。',
    'choose_first_night' => '最初の泊を選択してください。',
    'choose_last_night' => '最後の泊を選択してください。',
    'last_before_first' => '最後の泊を最初の泊より前にすることはできません。',
    'price_required' => '1泊料金を入力してください。',
    'read_only' => 'ワークスペースは現在読み取り専用のため、料金の変更は一時停止されています。',
    'price_out_of_range' => '0より大きく1,000,000以下の1泊料金を入力してください。',
    'season_saved' => 'シーズンを保存しました。影響範囲を確認してから適用すると、該当する泊に料金が書き込まれます。',
    'already_applied' => 'このシーズンはすでに適用されているため、編集できません。適用された1泊料金は既存の料金を置き換えており、以前の料金は記録されていません。代わりに、該当する泊に新しいシーズンを作成してください。',
    'season_updated' => 'シーズンを更新しました。影響範囲を確認してから適用すると、該当する泊に料金が書き込まれます。',
    'deleted_after_apply' => 'シーズン「:season」を削除しました。⚠️すでに適用された1泊料金は変わりません。シーズンを削除しても、置き換えられた料金は元に戻りません。料金が正しくない場合は、該当する泊の料金を設定し直してください。',
    'deleted_not_applied' => 'シーズン「:season」を削除しました。適用されていなかったため、料金は変更されていません。',
    'applied' => ':seasonを適用しました。:unitsの:nights分の料金を書き換えました。',
    'nights_count' => '{1}:count泊|[0,*]:count泊',
    'units_count' => '{1}:count室|[0,*]:count室',
    'overlapping_reapplied' => '{1}重複する:count件のシーズンを優先順位に従って再適用しました。|[0,*]重複する:count件のシーズンを優先順位に従って再適用しました。',
    'pick_unit_to_continue' => '続けるには客室を選択してください。',
    'stay_nights_unpriced' => '{1}この宿泊のうち:count泊には、まだ料金が設定されていません。|[0,*]この宿泊のうち:count泊には、まだ料金が設定されていません。',
];
