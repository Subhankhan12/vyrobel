<?php

// I18N-01 — tr translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'İptal edilmiş bir rezervasyon için check-in yapılamaz: rezervasyon kapanmıştır ve oda başkasına satılmış olabilir. Geri dönen bir misafir için yeni bir rezervasyon gerekir.',
    'refused_no_show' => 'No-show olarak işaretlenmiş bir rezervasyon için check-in yapılamaz: rezervasyon kapanmıştır ve geceleri yeniden satışa açılmıştır. Bu misafiri bir odaya yerleştirmek için ön büroda yeni bir rezervasyon oluşturun — rezervasyon yapmadan önce müsaitliği yeniden kontrol eder. No-show kaydı ve bunun için alınmış ücretler misafir kaydında kalır.',
    'refused_checked_out' => 'Check-out yapılmış bir rezervasyon için check-in yapılamaz: çıkış zaten kaydedilmiş ve işleme alınmıştır. Check-out işlemini geri alma seçeneği kullanılamaz.',
    'refused_tentative' => 'Opsiyonlu bir rezervasyon için check-in yapılamaz: oda ayrılmış ancak onaylanmamıştır, yani bu misafire konaklama henüz kimse tarafından taahhüt edilmemiştir. Önce bu rezervasyonda “Ayırmayı onayla” düğmesine basın, ardından check-in yapın. Geceler zaten bu rezervasyon için ayrılmış olduğundan onaylama işlemi ek bir şey ayırmaz ve müsaitlik nedeniyle başarısız olamaz.',
    'refused_other' => '“:status” durumundaki bir rezervasyon için check-in yapılamaz.',
];
