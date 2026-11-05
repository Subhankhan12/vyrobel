<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Komisyonu yüzde olarak girin.',
    'commission_range' => 'Komisyon 0 ile 100 arasında bir sayı olmalıdır.',
    'commission_unchanged' => 'Hiçbir şey değişmedi — :name için komisyon :date tarihinden itibaren zaten :rate.',
    'commission_from_today' => ':name için komisyon bugünden itibaren :rate — yürürlükte bir sözleşmesi yoktu.',
    'commission_waiting_cancelled' => ':name için komisyon :rate olarak kalıyor — :date tarihinde yapılacak değişiklik uygulanmayacak.',
    'commission_from_date' => ':name için komisyon :date tarihinden itibaren :rate — bundan sonra hesaplanan rezervasyonlar için. Zaten hesaplanmış rezervasyonlar kendi oranlarını korur.',
    'payouts_paused' => ':name için hakedişler duraklatıldı — siz sürdürene kadar bu mülk sahibine hakediş ödemesi yapılmaz.',
    'payouts_resumed' => ':name için hakedişler sürdürüldü.',
    'period_start_required' => 'Dönemin ilk gününü seçin.',
    'period_end_required' => 'Dönemin son gününü seçin.',
    'period_end_after_start' => 'Dönem, başladığı günle aynı gün veya daha sonra bitmelidir.',
    'period_not_closed' => 'Bu dönem kapatılamadı: :reason Son kapatılan dönemden sonra başlayan bir dönem seçin.',
    'period_sealed' => 'Kesinleşmiş veya kilitlenmiş bir hesap özeti yeniden oluşturulamaz.',
    'period_not_closed_other' => 'Bu döneme ait hesap özeti oluşturulamadı.',
    'period_closed' => 'Dönem kapatıldı — #:number numaralı hesap özeti kesinleşti, ödenecek tutar: :amount. Misafirlerden henüz tahsil edilmemiş gelir bu hesap özetine alınmaz ve sonraki bir hesap özetinde görünür.',
    'note_required' => 'Önce notu yazın.',
    'note_max' => 'Not en fazla 2.000 karakter olabilir.',
    'note_added' => 'Not eklendi.',
    'remove_password_required' => 'Bu mülk sahibini kaldırmak için şifrenizi girin.',
    'password_wrong' => 'Şifre yanlış — oturum açarken kullandığınız şifreyi girin.',
    'confirm_name_required' => 'Onaylamak için mülk sahibinin adını yazın.',
    'confirm_name_mismatch' => 'Yazdığınız ad eşleşmiyor — mülk sahibi kaldırılmadı.',
    'owner_removed' => ':name kaldırıldı — birimlerin ataması kaldırıldı, tüm hesap özetleri ve hakedişler muhasebe için saklandı.',
    'workspace_paused_until' => 'Kuruluş duraklatıldı — mülk sahipleri :date tarihine kadar salt okunur.',
    'workspace_paused_for_now' => 'Kuruluş duraklatıldı — mülk sahipleri şimdilik salt okunur.',
];
