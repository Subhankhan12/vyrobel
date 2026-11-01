<?php

// I18N-01 — tr translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Bu, check-out kontrolünü aşmak için tanınan bir neden değil.',
    'override_note_required' => '“Diğer” nedeniyle kontrolü aşmak için ne olduğunu açıklayan bir not gerekir.',
    'refused_confirmed' => 'Onaylanmış bir rezervasyon için check-out yapılamaz: bir konaklamada check-out yapılabilmesi için önce check-in yapılmış olması gerekir. Misafir konakladıysa ve erken ayrılıyorsa önce check-in, ardından check-out yapın. Hiç gelmediyse rezervasyonu bunun yerine no-show olarak işaretleyin.',
    'refused_closed' => '“:status” durumundaki bir rezervasyon için check-out yapılamaz: rezervasyon kapanmıştır ve konaklama gerçekleşmemiştir. Sonradan gelen bir misafir için yeni bir rezervasyon gerekir.',
    'refused_other' => '“:status” durumundaki bir rezervasyon için check-out yapılamaz.',
];
