<?php

// I18N-01 — zh_TW translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '住宿將恢復為已入住。',
    'pending_tasks_removed' => '{1}將移除 :count 項離店清潔任務——尚未開始。|[0,*]將移除 :count 項離店清潔任務——尚未開始。',
    'started_tasks_kept' => '{1}:count 項清潔任務已開始，將維持原樣——取消他人已開始的工作須由房務決定，不屬於撤銷範圍。|[0,*]:count 項清潔任務已開始，將維持原樣——取消他人已開始的工作須由房務決定，不屬於撤銷範圍。',
    'unit_stays_dirty' => '房間仍標示為未清潔。房客已回到房間，因此「已清潔」的狀態可能不符實際。',
    'notifications_stay_delivered' => '已傳送給員工與房客的通知不會收回。',
    'review_unknown' => '我們沒有此住宿是否已寄出評價邀請的紀錄，因為此住宿是在本系統開始記錄之前退房的。評價邀請可能已寄出，也可能沒有。',
    'review_sent_on' => '已於 :date 以電子郵件向房客寄出評價邀請，無法收回。',
    'review_sent' => '已以電子郵件向房客寄出評價邀請，無法收回。',
    'review_suppressed_by_operator' => '未寄出評價邀請——此館別已關閉評價邀請郵件。',
    'review_suppressed_by_guest' => '未寄出評價邀請——房客已表示不接收評價郵件。',
    'review_not_sent' => '未寄出評價邀請——沒有可用的聯絡電子郵件，或寄送失敗。',
    'refused_not_checked_out' => '只有已退房的住宿可以撤銷退房；此住宿的狀態為「:status」。',
];
