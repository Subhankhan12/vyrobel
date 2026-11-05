<?php

// I18N-01 — id translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Masukkan alamat IP atau blok CIDR yang valid (contoh: 203.0.113.4 atau 203.0.113.0/24).',
    'ip_already_listed' => ':ip sudah ada di daftar izin IP.',
    'allowlist_enabled_auto_added' => 'Daftar izin IP diaktifkan. Kami juga menambahkan alamat Anda saat ini (:ip) agar Anda tidak terkunci.',
    'ip_added' => ':ip berhasil ditambahkan ke daftar izin IP.',
    'remove_would_lock_out' => 'Menghapus :ip akan membuat Anda terkunci — ini satu-satunya aturan yang mengizinkan alamat Anda (:current) mengakses back office. Tambahkan aturan untuk alamat Anda terlebih dahulu, atau hapus aturan lainnya.',
    'ip_removed_allowlist_off' => ':ip berhasil dihapus — daftar izin IP nonaktif: siapa pun yang dapat masuk dengan sah dapat mengakses back office.',
    'ip_removed' => ':ip berhasil dihapus dari daftar izin IP.',
    'session_already_ended' => 'Sesi tersebut sudah berakhir.',
    'signed_out_of_device' => 'Berhasil keluar dari :device.',
    'trusted_device_revoked' => 'Perangkat tepercaya berhasil dicabut.',
    'csv_title' => 'Ekspor akun Vyrobel',
    'csv_name' => 'Nama',
    'csv_email' => 'Email',
    'csv_locale' => 'Bahasa',
    'csv_roles' => 'Peran',
    'csv_activity' => 'Aktivitas',
    'csv_when' => 'Waktu',
    'csv_action' => 'Tindakan',
    'range_this_year' => 'Tahun ini',
    'range_custom' => 'Rentang khusus',
    'range_all_time' => 'Sepanjang waktu',
];
