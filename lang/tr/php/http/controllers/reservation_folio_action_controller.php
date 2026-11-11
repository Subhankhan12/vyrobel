<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Kart ödemeleri Stripe üzerinden (test modu) alınır — canlı anahtara geçildiğinde kullanılabilir olacak. Ön büroda hemen ödeme almak için nakit veya banka havalesi kaydedin.',
    'payment_amount_invalid' => 'Sıfırdan büyük bir ödeme tutarı girin.',
    'payment_recorded' => 'Ödeme kaydedildi.',
    'charge_amount_invalid' => 'Sıfırdan büyük bir kalem tutarı girin.',
    'charge_added' => 'Kalem eklendi.',
    'refund_only_settled' => 'Yalnızca tahsil edilmiş bir ödeme iade edilebilir.',
    'already_fully_refunded' => 'Bu ödeme zaten tamamen iade edildi.',
    'refund_amount_invalid' => 'Sıfırdan büyük bir iade tutarı girin.',
    'refund_exceeds' => 'İade reddedildi — bu ödeme için yalnızca :amount :currency daha iade edilebilir.',
    'refund_issued' => 'İade yapıldı.',
    'billed_to_account' => 'Hesaba faturalandı: :account. Hesap ödeme yapana kadar bakiye ödenmemiş olarak kalır.',
    'returned_to_guest' => 'Misafirin folyosuna geri aktarıldı.',
    'hold_released' => 'Provizyon serbest bırakıldı — misafirin kartı serbest kaldı ve hiçbir tutar tahsil edilmedi.',
];
