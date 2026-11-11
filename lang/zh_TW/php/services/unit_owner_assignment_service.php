<?php

// I18N-01 — zh_TW translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => '無法為 :owner 分配房間——對方尚未接受邀請，或已被封存。只有啟用中的業主才能被分配房間。',
    'unit_already_owned' => '「:unit」已歸屬 :owner，因此無法在此分配給其他人。只能分配尚無業主的房間。',
    'unit_owned_by_another' => '其他業主',
    'date_invalid' => '請選擇有效的日期，作為分配房間的起始日期。',
    'date_in_past' => '請選擇今天或之後的日期。已過去的日期會變動已計算的業主收入。',
    'date_too_far' => '請選擇未來 :days 天內的日期。',
    'no_agreement' => ':owner 在 :date 沒有涵蓋「:unit」的代管合約。請先為 :owner 建立自 :date 起涵蓋此房間的代管合約，再分配房間。',
    'stay_across_date' => '有房客從 :from 至 :to 住在「:unit」，橫跨 :date。請選擇 :to 或之後的日期，避免同一筆住宿由您與 :owner 分攤。',
    'future_stays_with_another_owner' => '「:unit」在 :date 或之後有已為其他業主（:owners）計算收入的住宿。這些住宿仍歸對方，因此無法自該日起分配此房間。',
];
