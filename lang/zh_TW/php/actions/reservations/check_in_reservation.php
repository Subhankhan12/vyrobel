<?php

// I18N-01 — zh_TW translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => '無法為已取消的訂房辦理入住：此訂房已結束，房間可能已再次售出。房客若要再次入住，需要建立新的訂房。',
    'refused_no_show' => '無法為已標記為未抵達的訂房辦理入住：此訂房已結束，其晚數已釋出並重新開放販售。若要安排此房客入住，請在櫃檯辦理「現場散客」——系統會在訂房前重新檢查可售房數。該筆未抵達紀錄與已收取的任何手續費會保留在房客紀錄中。',
    'refused_checked_out' => '無法為已退房的訂房辦理入住：離店已記錄並處理完畢。若退房是誤按，請先在該訂房上使用「撤銷退房」。',
    'refused_tentative' => '無法為暫定訂房辦理入住：房間已保留但尚未確認，因此尚未向此房客承諾這次住宿。請先在此訂房上點選「確認暫定保留」，再辦理入住。這些晚數已由此訂房保留，因此確認不會再佔用任何房量，也不會因可售房數而失敗。',
    'refused_other' => '無法為狀態「:status」的訂房辦理入住。',
];
