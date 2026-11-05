<?php

// I18N-01 — zh_CN translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '住宿将恢复为已入住。',
    'pending_tasks_removed' => '{1}将移除 :count 项离店清洁任务——尚未开始。|[0,*]将移除 :count 项离店清洁任务——尚未开始。',
    'started_tasks_kept' => '{1}:count 项清洁任务已经开始，将保持不变——取消他人已开始的工作应由客房部决定，而不是靠撤销操作。|[0,*]:count 项清洁任务已经开始，将保持不变——取消他人已开始的工作应由客房部决定，而不是靠撤销操作。',
    'unit_stays_dirty' => '房间仍标记为脏房。客人已回到房间，因此“净房”可能并不属实。',
    'notifications_stay_delivered' => '已发送给员工和客人的通知仍保持已送达。',
    'review_unknown' => '我们没有记录此住宿是否已发送评价邀请，因为该住宿在本产品开始记录评价邀请之前就已退房。评价邀请可能已发送，也可能未发送。',
    'review_sent_on' => '已于 :date 通过邮件向客人发送评价邀请。该邮件无法撤回。',
    'review_sent' => '已通过邮件向客人发送评价邀请。该邮件无法撤回。',
    'review_suppressed_by_operator' => '未发送评价邀请——此门店已关闭评价邀请邮件。',
    'review_suppressed_by_guest' => '未发送评价邀请——客人已要求不接收评价邮件。',
    'review_not_sent' => '未发送评价邀请——没有联系地址，或发送失败。',
    'refused_not_checked_out' => '只有已退房的住宿才能撤销退房；此住宿的状态为“:status”。',
];
