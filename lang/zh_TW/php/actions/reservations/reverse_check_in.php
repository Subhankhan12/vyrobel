<?php

// I18N-01 — zh_TW translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '住宿將恢復為已確認，房間仍會保留相同的晚數。',
    'history_kept' => '入住紀錄本身會保留在訂房歷程中。入住確實發生過，紀錄也如實記載——撤銷會另外新增一筆紀錄。',
    'charges_kept' => '{1}入住後已入帳 :count 筆消費，共 :amount，仍保留在帳單上。撤銷入住不會讓已產生的消費消失——若這些消費也是誤記，請在帳單上移除。|[0,*]入住後已入帳 :count 筆消費，共 :amount，仍保留在帳單上。撤銷入住不會讓已產生的消費消失——若這些消費也是誤記，請在帳單上移除。',
    'online_check_in_kept' => '房客自行完成的線上辦理入住仍維持已完成。房客確實填寫過，此操作不會要求重新填寫。',
    'already_reversed' => '該筆入住剛剛已撤銷——住宿已恢復為已確認。',
    'refused_not_checked_in' => '只有已入住的住宿可以撤銷入住；此住宿的狀態為「:status」。',
];
