<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Bu bağlantı artık mevcut değil.',
    'unlink_refused_only_way_in' => 'Reddedildi — bu kişinin oturum açabileceği tek yol bu (şifresi yok, başka sağlayıcısı yok). Bağlantıyı kaldırmak kişinin erişimini kalıcı olarak keser.',
    'unlinked' => ':provider bağlantısı kaldırıldı — geri okundu: bağlantı artık yok.',
    'unlink_not_landed' => 'Bağlantı kaldırma işlemi gerçekleşmedi — bağlantı hâlâ duruyor.',
    'already_verified' => 'Zaten doğrulanmış.',
    'marked_verified' => 'Doğrulandı olarak işaretlendi — veritabanından geri okundu: :stored UTC.',
    'verify_not_landed' => 'Yazma işlemi gerçekleşmedi — email_verified_at hâlâ boş.',
    'already_verified_nothing_to_resend' => 'Zaten doğrulanmış — yeniden gönderilecek bir şey yok.',
    'verification_discarded' => 'TESLİM EDİLMEDİ — e-posta gönderimi “:mailer” olarak ayarlı, bu yüzden doğrulama e-postası atıldı. Kimse bir şey almadı. Kişinin kimliğini başka bir yolla doğrulayın, ardından “E-postayı doğrulandı olarak işaretle” işlemini kullanın.',
    'verification_handed' => 'Doğrulama e-postası e-posta gönderim sistemine iletildi (:mailer).',
    'no_reset_link' => 'Sıfırlama bağlantısı oluşturulmadı: :status',
    'reset_discarded' => 'TESLİM EDİLMEDİ — bir sıfırlama bağlantısı oluşturuldu, ancak e-posta gönderimi “:mailer” olarak ayarlı, bu yüzden e-posta atıldı. Kimse bir şey almadı.',
    'reset_handed' => 'Şifre sıfırlama e-postası e-posta gönderim sistemine iletildi (:mailer).',
    'verification_not_sent_undeliverable' => 'GÖNDERİLMEDİ — bu adres hiçbir zaman e-posta alamaz (:reason), bu yüzden doğrulama e-postası e-posta gönderim sistemine iletilmedi. Kimse bir şey almadı.',
    'reset_not_sent_undeliverable' => 'GÖNDERİLMEDİ — bu adres hiçbir zaman e-posta alamaz (:reason), bu yüzden sıfırlama bağlantısı e-posta gönderim sistemine iletilmedi. Kimse bir şey almadı.',
];
