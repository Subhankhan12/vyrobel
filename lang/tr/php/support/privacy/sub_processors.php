<?php

// I18N-01 — tr translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Kayıtlı sözleşme yok',
    'state_switched_on' => 'Aktif — açık',
    'state_not_active' => 'Aktif değil',
    'state_configured' => 'Yapılandırıldı',
    'hostinger_hosting' => [
        'name' => 'Hostinger (barındırma)',
        'purpose' => 'Uygulamayı ve veritabanını çalıştırır.',
        'data' => 'Ürünün sakladığı her şey — tüm hesap, otel ve misafir kayıtları.',
        'state' => 'Aktif — canlı ortam sunucusu',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Herkese açık siteyi barındırma sunucusunun önünde sunar ve önbelleğe alır.',
        'data' => 'Üzerinden geçen her istek: IP adresleri, URL adresleri, aktarım sırasındaki çerezler.',
        'state' => 'Aktif — her yanıtta gözlemleniyor',
    ],
    'google' => [
        'name' => 'Google (oturum açma)',
        'purpose' => 'Google hesabıyla oturum açma ve kaydolma.',
        'data' => 'Kişinin Google kimliği: hesap kimliği, e-posta adresi, ad.',
    ],
    'x' => [
        'name' => 'X (oturum açma)',
        'purpose' => 'X hesabıyla oturum açma ve kaydolma.',
        'data' => 'Kişinin X kimliği: hesap kimliği, kullanıcı adı, ad.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-posta)',
        'purpose' => 'Tüm işlem e-postalarını vyrobel.com posta kutusundan iletir.',
        'data' => 'Alıcı adresleri ve mesaj içerikleri — misafir adları, rezervasyon numaraları, tarihler.',
        'state_active' => 'Aktif — e-posta gönderim sistemi',
        'state_inactive' => 'Aktif değil — e-posta gönderimi “:mailer” olarak ayarlı',
    ],
    'stripe' => [
        'name' => 'Stripe (ödemeler ve faturalandırma)',
        'purpose' => 'Abonelik faturalandırması, misafir kart ödemeleri, mülk sahibi hakedişleri (Connect).',
        'data' => 'Fatura iletişim bilgileri, tutarlar, rezervasyon numaraları; mülk sahibi hakedişleri için KYC kapsamında kimlik bilgileri.',
        'state_fake' => 'Aktif değil — sahte bir istemci bağlı',
    ],
    'anthropic' => [
        'name' => 'Anthropic (yapay zekâ asistanı)',
        'purpose' => 'Yapay zekâ asistanında soruları yanıtlar.',
        'data' => 'Sorulan soru ve onunla birlikte gönderilen bağlam.',
        'state_off' => 'Aktif değil — sahte istemci veya anahtar yok',
    ],
    'channex' => [
        'name' => 'Channex (kanal yöneticisi)',
        'purpose' => 'Fiyatları, müsaitliği ve rezervasyonları OTA’larla senkronize eder.',
        'data' => 'Kanallarla karşılıklı aktarılan rezervasyon ve misafir bilgileri.',
        'state_off' => 'Bağlı değil',
    ],
];
