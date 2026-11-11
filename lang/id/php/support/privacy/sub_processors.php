<?php

// I18N-01 — id translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Belum tercatat',
    'state_switched_on' => 'Aktif — sudah diaktifkan',
    'state_not_active' => 'Nonaktif',
    'state_configured' => 'Dikonfigurasi',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Menjalankan aplikasi beserta basis datanya.',
        'data' => 'Semua yang disimpan produk — setiap catatan akun, hotel, dan tamu.',
        'state' => 'Aktif — server produksi',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Menyajikan dan menyimpan cache situs publik di depan server.',
        'data' => 'Setiap permintaan yang melewatinya: alamat IP, URL, dan cookie yang sedang dikirim.',
        'state' => 'Aktif — teramati pada setiap respons',
    ],
    'google' => [
        'name' => 'Google (proses masuk)',
        'purpose' => 'Masuk dan mendaftar dengan akun Google.',
        'data' => 'Identitas Google orang tersebut: ID akun, email, nama.',
    ],
    'x' => [
        'name' => 'X (proses masuk)',
        'purpose' => 'Masuk dan mendaftar dengan akun X.',
        'data' => 'Identitas X orang tersebut: ID akun, nama pengguna, nama.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (email)',
        'purpose' => 'Mengirimkan setiap email transaksional, dari kotak surat vyrobel.com.',
        'data' => 'Alamat penerima dan isi pesan — nama tamu, kode reservasi, tanggal.',
        'state_active' => 'Aktif — layanan email',
        'state_inactive' => 'Nonaktif — email diatur ke “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (pembayaran & penagihan)',
        'purpose' => 'Penagihan langganan, pembayaran kartu tamu, pencairan pemilik (Connect).',
        'data' => 'Kontak penagihan, jumlah, kode reservasi; untuk pencairan pemilik, data identitas untuk KYC.',
        'state_fake' => 'Nonaktif — klien tiruan sedang terpasang',
    ],
    'anthropic' => [
        'name' => 'Anthropic (asisten AI)',
        'purpose' => 'Menjawab pertanyaan di asisten AI.',
        'data' => 'Pertanyaan yang diajukan dan konteks yang dikirim bersamanya.',
        'state_off' => 'Nonaktif — klien tiruan atau tidak ada kunci',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Menyinkronkan tarif, ketersediaan, dan reservasi dengan OTA.',
        'data' => 'Detail reservasi dan tamu yang dipertukarkan dengan saluran.',
        'state_off' => 'Belum terhubung',
    ],
];
