<?php

// I18N-01 — zh_CN translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => '这不是可识别的欠款退房原因。',
    'override_note_required' => '选择“其他”原因的欠款退房需要填写备注，说明发生了什么。',
    'refused_confirmed' => '无法为已确认的预订办理退房：住宿必须先办理入住，才能办理退房。如果客人已入住并提前离开，请先为其办理入住，再办理退房。如果客人从未到店，请将该预订标记为未到店。',
    'refused_closed' => '无法为状态为“:status”的预订办理退房：该预订已关闭，且未发生住宿。如果客人最终到店，需要新建预订。',
    'refused_other' => '无法为状态为“:status”的预订办理退房。',
];
