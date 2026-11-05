<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/reservation_create_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'workspace_paused' => '您的组织已暂停（只读）——恢复之前无法新建预订。请在“计费”中恢复。',
    'invalid_dates' => '请选择有效的入住日期和离店日期。',
    'too_many_nights' => '一个预订最多 :max 晚——请选择更短的住宿。',
    'choose_room_and_unit' => '请为此次住宿选择房型和具体房间。',
    'room_type_unavailable' => '该房型不可用。',
    'room_not_free_or_unpriced' => '该房间不空闲，或这些日期中有夜晚没有价格。请重新从列表中选择房间。',
    'unit_no_longer_free' => '该房间在这些日期已不再空闲——请选择其他房间。',
    'card_coming_soon' => '银行卡付款即将推出——请现在收取现金或银行转账，或稍后在账单中登记。',
    'no_payment_permission' => '您没有登记收款的权限。',
    'invalid_payment_amount' => '请输入有效的付款金额，例如：150 或 150.00。',
    'choose_override_reason' => '如需仍然预订，请从列表中选择原因。',
    'override_note_required' => '请在备注中说明原因——选择“其他”时必须填写。',
    'dates_restricted' => '未创建此预订——这些日期有限制。请在下方填写原因后仍然预订，或在第 1 步中更改日期。',
    'unit_just_taken' => '该房间刚刚在这些日期被预订——未创建任何预订。请选择其他房间。',
    'reservation_created' => '预订 :reference 已创建。',
    'credit_applied' => '已抵扣余额 :amount。',
    'recorded_as_paid' => '已将 :amount 登记为已付款。',
    'walk_in_checked_in' => '上门散客预订 :reference 已办理入住。',
    'walk_in_created' => '上门散客预订 :reference 已创建。',
    'guest_not_found' => '找不到该客人——请重新搜索。',
    'choose_or_name_guest' => '请选择现有客人，或输入新客人的姓名。',
    'invalid_guest_email' => '该客人邮箱地址无效。',
];
