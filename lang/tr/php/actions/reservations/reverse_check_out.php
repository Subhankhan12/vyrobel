<?php

// I18N-01 — tr translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Konaklama “Check-in yapıldı” durumuna döner.',
    'pending_tasks_removed' => '{1} :count çıkış temizliği görevi kaldırılacak — henüz başlanmadı.|[0,*] :count çıkış temizliği görevi kaldırılacak — henüz başlanmadı.',
    'started_tasks_kept' => '{1} :count temizlik görevine zaten başlandı ve olduğu gibi bırakılacak — birinin başladığı işi iptal etmek bir geri alma işlemi değil, kat hizmetlerinin vereceği bir karardır.|[0,*] :count temizlik görevine zaten başlandı ve olduğu gibi bırakılacak — birinin başladığı işi iptal etmek bir geri alma işlemi değil, kat hizmetlerinin vereceği bir karardır.',
    'unit_stays_dirty' => 'Birim kirli olarak işaretli kalır. Misafir odaya geri döndü, bu yüzden “temiz” doğru olmayabilir.',
    'notifications_stay_delivered' => 'Personele ve misafire zaten iletilmiş bildirimler iletilmiş olarak kalır.',
    'review_unknown' => 'Bu konaklama için değerlendirme isteği gönderilip gönderilmediğine dair bir kaydımız yok; çünkü bu konaklamanın check-out işlemi, ürün bu istekleri kaydetmeye başlamadan önce yapıldı. Gönderilmiş de olabilir, gönderilmemiş de.',
    'review_sent_on' => 'Misafire :date tarihinde değerlendirme isteği e-postası gönderildi. Bu e-posta geri çekilemez.',
    'review_sent' => 'Misafire değerlendirme isteği e-postası gönderildi. Bu e-posta geri çekilemez.',
    'review_suppressed_by_operator' => 'Değerlendirme isteği gönderilmedi — bu tesiste değerlendirme isteği e-postası kapalı.',
    'review_suppressed_by_guest' => 'Değerlendirme isteği gönderilmedi — misafir değerlendirme e-postaları almak istemediğini belirtti.',
    'review_not_sent' => 'Değerlendirme isteği gönderilmedi — iletişim adresi yoktu ya da gönderim başarısız oldu.',
    'refused_not_checked_out' => 'Yalnızca check-out yapılmış bir konaklamanın check-out işlemi geri alınabilir; bu konaklamanın durumu: :status.',
];
