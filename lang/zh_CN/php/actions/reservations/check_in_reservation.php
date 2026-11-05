<?php

// I18N-01 — zh_CN translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => '无法为已取消的预订办理入住：该预订已关闭，房间可能已再次售出。再次到来的客人需要新建预订。',
    'refused_no_show' => '无法为未到店的预订办理入住：该预订已关闭，其占用的夜晚已释放并重新开放销售。如需为此客人安排房间，请在前台办理“上门散客”——系统会在预订前重新检查可售房量。未到店记录及已收取的任何费用将保留在客人记录中。',
    'refused_checked_out' => '无法为已退房的预订办理入住：离店已登记并已处理。如果退房是误操作，请先在该预订上使用“撤销退房”。',
    'refused_tentative' => '无法为临时预订办理入住：房间已保留但尚未确认，因此尚未向此客人承诺住宿。请先在此预订上点击“确认临时预订”，再为其办理入住。这些夜晚已由此预订占用，因此确认不会再占用任何房量，也不会因可售房量不足而失败。',
    'refused_other' => '无法为状态为“:status”的预订办理入住。',
];
