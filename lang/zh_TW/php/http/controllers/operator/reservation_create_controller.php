<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/operator/reservation_create_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'workspace_paused' => '您的組織已暫停（唯讀）——恢復之前，無法建立新訂房。請至「帳務」恢復。',
    'invalid_dates' => '請選擇有效的入住日期與退房日期。',
    'too_many_nights' => '一筆訂房最多 :max 晚——請縮短住宿期間。',
    'choose_room_and_unit' => '請選擇房型與指定的房間。',
    'room_type_unavailable' => '該房型目前無法訂房。',
    'room_not_free_or_unpriced' => '該房間已非空房，或這段日期並非每晚都有房價。請重新從清單中選擇房間。',
    'unit_no_longer_free' => '該房間在這些日期已非空房——請選擇其他房間。',
    'card_coming_soon' => '信用卡付款即將推出——請先收取現金或銀行轉帳，或稍後從帳單記錄收款。',
    'no_payment_permission' => '您沒有記錄收款的權限。',
    'invalid_payment_amount' => '請輸入有效的付款金額，例如：150 或 150.00。',
    'choose_override_reason' => '若仍要訂房，請從清單中選擇原因。',
    'override_note_required' => '請在備註中說明原因——選擇「其他」時必填。',
    'dates_restricted' => '尚未建立此訂房——這些日期有訂房限制。您可在下方填寫原因後仍要訂房，或在第 1 步變更日期。',
    'unit_just_taken' => '該房間在這些日期剛被訂走——未建立任何訂房。請選擇其他房間。',
    'reservation_created' => '訂房 :reference 已建立。',
    'credit_applied' => '已使用 :amount 錢包餘額。',
    'recorded_as_paid' => '已將 :amount 記錄為已付款。',
    'walk_in_checked_in' => '現場散客 :reference 已入住。',
    'walk_in_created' => '現場散客 :reference 已建立。',
    'guest_not_found' => '找不到該房客——請重新搜尋。',
    'choose_or_name_guest' => '請選擇現有房客，或輸入新房客的姓名。',
    'invalid_guest_email' => '該房客的電子郵件地址無效。',
    'guest_email_exists' => '已有房客使用此電子郵件（:name）。請重新輸入電子郵件，並選擇「使用此房客」或「建立新房客」。',
];
