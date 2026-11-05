<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => '请输入佣金百分比。',
    'commission_range' => '佣金必须是 0 到 100 之间的数字。',
    'commission_unchanged' => '未作任何更改——自 :date 起，:name 的佣金已是 :rate。',
    'commission_from_today' => '自今天起，:name 的佣金为 :rate——此前没有生效的托管协议。',
    'commission_waiting_cancelled' => ':name 的佣金保持 :rate——原定于 :date 生效的更改将不会执行。',
    'commission_from_date' => '自 :date 起，:name 的佣金为 :rate——适用于此后计算的预订。已计算的预订保留原佣金率。',
    'payouts_paused' => '已暂停向 :name 打款——恢复前不会向其发放任何打款。',
    'payouts_resumed' => '已恢复向 :name 打款。',
    'period_start_required' => '请选择结算期的第一天。',
    'period_end_required' => '请选择结算期的最后一天。',
    'period_end_after_start' => '结算期的结束日期必须与开始日期相同或在其之后。',
    'period_not_closed' => '无法关闭该结算期：:reason请选择在最近一个已封存结算期之后开始的结算期。',
    'period_closed' => '结算期已关闭——结算单号 :number 已定稿，应付 :amount。尚未向客人收取的营收暂不结算，将出现在之后的结算单上。',
    'note_required' => '请先填写备注。',
    'note_max' => '备注最多 2,000 个字符。',
    'note_added' => '备注已添加。',
    'remove_password_required' => '请输入您的密码以归档此业主。',
    'password_wrong' => '密码不正确——请输入您登录时使用的密码。',
    'confirm_name_required' => '请输入业主名称以确认。',
    'confirm_name_mismatch' => '您输入的名称不匹配——业主未归档。',
    'owner_removed' => '已归档 :name——其房间已取消归属，所有结算单和打款均保留以供会计核算。',
    'workspace_paused_until' => '组织已暂停——:date 前业主信息的更改为只读。',
    'workspace_paused_for_now' => '组织已暂停——业主信息的更改目前为只读。',
];
