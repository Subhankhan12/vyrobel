<?php

// I18N-01 — zh_TW translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => '此退房略過原因無法辨識。',
    'override_note_required' => '以「其他」作為略過原因時，必須填寫備註，說明發生了什麼事。',
    'refused_confirmed' => '無法為已確認的訂房辦理退房：住宿必須先辦理入住，才能辦理退房。若房客曾入住且提前離開，請先辦理入住，再辦理退房；若房客從未抵達，請改為將此訂房標記為未抵達。',
    'refused_closed' => '無法為狀態「:status」的訂房辦理退房：此訂房已結束，且並未發生住宿。若房客最後還是到場，需要建立新的訂房。',
    'refused_other' => '無法為狀態「:status」的訂房辦理退房。',
];
