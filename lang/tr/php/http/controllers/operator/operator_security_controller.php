<?php

// I18N-01 — tr translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Geçerli bir IP adresi veya CIDR bloğu girin (örn. 203.0.113.4 veya 203.0.113.0/24).',
    'ip_already_listed' => ':ip zaten izin listesinde.',
    'allowlist_enabled_auto_added' => 'IP izin listesi etkinleştirildi. Erişiminizin kesilmemesi için mevcut adresinizi (:ip) de ekledik.',
    'ip_added' => ':ip izin listesine eklendi.',
    'remove_would_lock_out' => ':ip kaldırılırsa erişiminiz kesilir — adresinizin (:current) yönetim paneline ulaşmasına izin veren tek kural bu. Önce adresiniz için bir kural ekleyin veya diğerlerini kaldırın.',
    'ip_removed_allowlist_off' => ':ip kaldırıldı — izin listesi kapalı: geçerli oturum açma bilgileri olan herkes yönetim paneline erişebilir.',
    'ip_removed' => ':ip izin listesinden kaldırıldı.',
    'session_already_ended' => 'Bu oturum zaten sonlandırılmış.',
    'signed_out_of_device' => 'Oturum kapatıldı: :device.',
    'trusted_device_revoked' => 'Güvenilir cihaz kaldırıldı.',
    'csv_title' => 'Vyrobel hesap dışa aktarımı',
    'csv_name' => 'Ad',
    'csv_email' => 'E-posta',
    'csv_locale' => 'Yerel ayar',
    'csv_roles' => 'Roller',
    'csv_activity' => 'Etkinlik',
    'csv_when' => 'Zaman',
    'csv_action' => 'İşlem',
    'range_this_year' => 'Bu yıl',
    'range_custom' => 'Özel aralık',
    'range_all_time' => 'Tüm zamanlar',
];
