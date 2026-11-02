<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => '請選擇房價方案。',
    'name_required' => '請輸入季節名稱。',
    'name_too_long' => '季節名稱最多 80 個字元。',
    'choose_first_night' => '請選擇第一晚。',
    'choose_last_night' => '請選擇最後一晚。',
    'last_before_first' => '最後一晚不可早於第一晚。',
    'price_required' => '請輸入每晚價格。',
    'read_only' => '您的組織目前為唯讀狀態——房價變更已暫停。',
    'price_out_of_range' => '請輸入大於 0 且不超過 1,000,000 的每晚價格。',
    'season_saved' => '季節已儲存。請先檢視影響範圍，再套用以寫入這些晚數。',
    'already_applied' => '此季節已套用，因此無法編輯——它寫入的每晚房價已取代原有房價，且沒有任何紀錄保存先前的價格。請改為針對這些晚數建立新的季節。',
    'season_updated' => '季節已更新。請先檢視影響範圍，再套用以寫入這些晚數。',
    'deleted_after_apply' => '季節「:season」已刪除。⚠️ 該季節已套用的每晚房價維持不變——刪除季節不會還原被取代的價格。若這些晚數的房價有誤，請自行重新設定。',
    'deleted_not_applied' => '季節「:season」已刪除。此季節尚未套用，因此房價沒有變動。',
    'applied' => '「:season」已套用——已重新寫入 :nights，涵蓋 :units。',
    'nights_count' => '{1}:count 晚|[0,*]:count 晚',
    'units_count' => '{1}:count 間房|[0,*]:count 間房',
    'overlapping_reapplied' => '{1}已依優先順序重新套用 :count 個重疊的季節。|[0,*]已依優先順序重新套用 :count 個重疊的季節。',
    'pick_unit_to_continue' => '請選擇房間以繼續。',
    'stay_nights_unpriced' => '{1}此段住宿仍有 :count 晚尚無房價。|[0,*]此段住宿仍有 :count 晚尚無房價。',
];
