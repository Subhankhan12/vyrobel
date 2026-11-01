<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'Hakediş motoru devre dışı (PAYOUT_ENGINE_ENABLED). Toplu hakedişi başlatmadan önce etkinleştirin.',
    'nothing_to_run' => 'Başlatılacak bir şey yok — buradaki hiçbir mülk sahibinin ödenecek hesap özeti veya bloke hakedişi yok:paused_note.',
    'run_complete' => 'Toplu hakediş tamamlandı (mod: :mode) — oluşturulan: :created · ödenen: :paid · bloke: :held:paused_note.',
    'create_failed' => 'Hakediş oluşturulamadı — :reason',
    'already_has_payout' => 'Bu hesap özetinin zaten bir hakedişi var — hiçbir şey oluşturulmadı.',
    'one_payout_created' => '1 hakediş oluşturuldu.',
    'pay_failed' => 'Ödeme yapılamadı — :reason',
    'one_payout_paid' => '1 hakediş ödendi olarak işaretlendi.',
    'payout_failed_reason' => 'Hakediş başarısız oldu — :reason',
    'payout_failed_no_reason' => 'Hakediş başarısız oldu — ödeme kanalı bir neden bildirmedi.',
    'create_all_failed' => 'Hakedişler oluşturulamadı — :reason',
    'no_payout_created_paused' => 'Hakediş oluşturulmadı:paused_note — mülk sahibinin sayfasından sürdürün.',
    'nothing_ready' => 'Ödenmeye hazır bir şey yok — hiçbir kesinleşmiş hesap özetinde pozitif ödenecek net tutar yok:paused_note.',
    'no_new_payouts' => 'Yeni hakediş yok — hazır her hesap özetinin zaten bir hakedişi var:paused_note.',
    'payouts_created' => '{1} :count hakediş oluşturuldu:paused_note.|[0,*] :count hakediş oluşturuldu:paused_note.',
    'some_not_created' => ':message Bazıları oluşturulamadı — :reason',
    'nothing_paid_paused' => 'Hiçbir şey ödenmedi:paused_note — mülk sahibinin sayfasından sürdürün.',
    'no_pending_payouts' => 'Ödenecek bekleyen hakediş yok.',
    'paid_only' => '{1} :count hakediş ödendi olarak işaretlendi:paused_note.|[0,*] :count hakediş ödendi olarak işaretlendi:paused_note.',
    'failed_only' => '[0,*] :count hakediş ödeme kanalında başarısız oldu:paused_note.',
    'paid_and_failed' => '{1} :count hakediş ödendi olarak işaretlendi · ödeme kanalında başarısız: :failed:paused_note.|[0,*] :count hakediş ödendi olarak işaretlendi · ödeme kanalında başarısız: :failed:paused_note.',
    'some_not_attempted' => ':message Bazıları denenemedi — :reason',
    'nothing_payable_negative' => 'Ödenecek bir şey yok — bu hesap özetinin neti negatif, bu nedenle bakiye mülk sahibinin sonraki hesap özetine mahsup edilmek üzere devreder.',
    'nothing_payable_zero' => 'Ödenecek bir şey yok — bu hesap özetinin neti sıfır, dolayısıyla borç yok.',
    'workspace_paused_until' => 'Kuruluş duraklatıldı — hakedişler :date tarihine kadar salt okunur.',
    'workspace_paused_for_now' => 'Kuruluş duraklatıldı — hakedişler şimdilik salt okunur.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1} hakedişleri duraklatılmış :count mülk sahibi atlandı|[0,*] hakedişleri duraklatılmış :count mülk sahibi atlandı',
];
