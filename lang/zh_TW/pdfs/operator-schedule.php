<?php

// I18N-01 — zh_TW translation of lang/en/pdfs/operator-schedule.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'eyebrow' => '團隊排班',
    'week_of' => ':week的一週',
    'doc_title' => '每週排班表',
    'doc_sub' => ':property · :week的一週 · 產生時間：:generated',
    'published' => '發布日期：:date',
    'published_by' => '由 :name 於 :date 發布',
    'not_published' => '尚未發布——本週排班尚未傳送給團隊。',
    'team' => '團隊',
    'shifts' => '班別',
    'hours' => '時數',
    'open' => '待認領',
    'conflicts' => '衝突',
    'no_staff' => '所選週別的排班表上沒有員工。',
    'team_member' => '團隊成員',
    'cell_conflict' => ':shift / 請假！',
    'cell_leave' => '請假',
    'legend' => '<strong>如何閱讀此排班表：</strong>每個格子會顯示班別名稱與時間 · <strong>請假</strong> = 已核准的請假 · <strong>—</strong> = 未排班 · <strong>「/ 請假！」</strong> = 排在已核准請假日的班別。每種狀態都以文字寫出，因此黑白列印也能正確閱讀。',
    'shift_hours' => ':early :early_hours · :day :day_hours · :late :late_hours · 各格內顯示班別時間。',
    'conflict_count' => '{1}<strong style="color:#B0322A">:count 項衝突</strong>——排在已核准請假日的班別。|[0,*]<strong style="color:#B0322A">:count 項衝突</strong>——排在已核准請假日的班別。',
    'open_shifts' => '待認領班別',
    'all_assigned' => '此排班表上的每個班別都已有人負責——本週沒有待認領的時段。',
    'any_department' => '所有部門',
    'time_off' => '請假',
    'foot_generated' => ':property · 產生時間：:generated',
    'foot_week' => '週排班表 · :week',
    'snapshot_note' => '此排班表是排班在 :generated 當時的快照——之後的任何變更都不會出現在這份列印內容中。換班申請請透過 Vyrobel 應用程式或櫃檯提出。',
];
