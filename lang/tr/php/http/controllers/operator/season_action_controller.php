<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Bir fiyat planı seçin.',
    'name_required' => 'Sezona bir ad verin.',
    'name_too_long' => 'Sezon adı en fazla 80 karakter olabilir.',
    'choose_first_night' => 'İlk geceyi seçin.',
    'choose_last_night' => 'Son geceyi seçin.',
    'last_before_first' => 'Son gece, ilk geceden önce olamaz.',
    'price_required' => 'Gecelik fiyat girin.',
    'read_only' => 'Kuruluşunuz şu anda salt okunur — fiyat değişiklikleri duraklatıldı.',
    'price_out_of_range' => 'Sıfırdan büyük ve en fazla 1.000.000 olan bir gecelik fiyat girin.',
    'season_saved' => 'Sezon kaydedildi. Neleri değiştireceğini inceleyin, ardından bu gecelere yazmak için uygulayın.',
    'already_applied' => 'Bu sezon zaten uygulandığı için düzenlenemez — yazdığı gecelik fiyatlar oradaki fiyatların yerini aldı ve önceki fiyatlar hiçbir yerde kaydedilmedi. Bunun yerine bu geceler için yeni bir sezon oluşturun.',
    'season_updated' => 'Sezon güncellendi. Neleri değiştireceğini inceleyin, ardından bu gecelere yazmak için uygulayın.',
    'deleted_after_apply' => '“:season” sezonu silindi. ⚠️ Daha önce uyguladığı gecelik fiyatlar değişmedi — bir sezonu silmek, yerini aldığı fiyatları geri getirmez. Yanlışsa bu gecelerin fiyatlarını kendiniz ayarlayın.',
    'deleted_not_applied' => '“:season” sezonu silindi. Uygulanmamıştı, bu yüzden hiçbir fiyat değişmedi.',
    'applied' => ':season sezonu uygulandı — :units genelinde :nights yeniden yazıldı.',
    'nights_count' => '{1} :count gece|[0,*] :count gece',
    'units_count' => '{1} :count birim|[0,*] :count birim',
    'overlapping_reapplied' => '{1} Çakışan :count sezon öncelik sırasına göre yeniden uygulandı.|[0,*] Çakışan :count sezon öncelik sırasına göre yeniden uygulandı.',
    'pick_unit_to_continue' => 'Devam etmek için bir birim seçin.',
    'stay_nights_unpriced' => '{1} Konaklamanın :count gecesi için hâlâ fiyat yok.|[0,*] Konaklamanın :count gecesi için hâlâ fiyat yok.',
];
