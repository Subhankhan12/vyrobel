<?php

// I18N-01 — zh_CN translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '住宿将恢复为已确认，房间在相同的夜晚仍为其保留。',
    'history_kept' => '入住记录本身仍保留在预订历史中。入住确实发生过，记录也如实反映——撤销记录会附加在其旁边。',
    'charges_kept' => '{1}自入住以来已入账 :count 笔消费，共计 :amount，仍保留在账单上。撤销入住不会撤回已发生的消费——如果这些消费也是误录，请在账单上移除。|[0,*]自入住以来已入账 :count 笔消费，共计 :amount，仍保留在账单上。撤销入住不会撤回已发生的消费——如果这些消费也是误录，请在账单上移除。',
    'online_check_in_kept' => '客人自己完成的在线办理入住仍为已完成状态。客人确实填写过，此操作不会要求其重新填写。',
    'already_reversed' => '该入住刚刚已撤销——住宿已恢复为已确认。',
    'refused_not_checked_in' => '只有已入住的住宿才能撤销入住；此住宿的状态为“:status”。',
];
