<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Yalnızca opsiyonlu bir ayırma onaylanabilir — bu rezervasyonun durumu: :status.',
    'hold_confirmed' => 'Ayırma onaylandı — rezervasyon artık onaylanmış bir konaklama.',
    'cannot_cancel_closed' => 'Bu rezervasyon iptal edilemez — zaten kapanmış.',
    'reason_too_long' => 'İptal nedeni çok uzun — en fazla 255 karakter.',
    'cancel_rejected' => 'Bu rezervasyon iptal edilemedi — rezervasyon servisi işlemi reddetti. Hiçbir şey değiştirilmedi.',
    'cancelled_refunded' => 'Rezervasyon iptal edildi + :amount iade edildi (ücretsiz iptal süresi içinde). Ayrılan geceler yeniden takvimde müsait.',
    'cancelled' => 'Rezervasyon iptal edildi. Ayrılan geceler yeniden takvimde müsait.',
    'cannot_no_show' => 'Bu rezervasyon no-show olarak işaretlenemez — yalnızca onaylanmış bir rezervasyon işaretlenebilir.',
    'no_show_rejected' => 'Bu rezervasyon no-show olarak işaretlenemedi — rezervasyon servisi işlemi reddetti. Hiçbir şey değiştirilmedi.',
    'marked_no_show' => 'No-show olarak işaretlendi. Ayrılan geceler yeniden takvimde müsait.',
];
