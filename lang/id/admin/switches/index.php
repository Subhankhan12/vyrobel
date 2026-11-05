<?php

// I18N-01 — id translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Sakelar darurat',
    'subtitle' => 'Hentikan pendaftaran baru, atau proses masuk melalui satu penyedia, tanpa deploy. Setiap perubahan diaudit beserta alasannya.',
    'explainer' => 'Setiap sakelar dalam keadaan <strong>aktif</strong> kecuali seseorang menonaktifkannya di sini — termasuk tepat setelah deploy. Tidak satu pun dapat memengaruhi konsol ini. Menonaktifkan <strong>proses masuk</strong> melalui sebuah penyedia akan mengunci siapa pun yang hanya bisa masuk melalui penyedia tersebut, jadi sakelar itu menampilkan jumlahnya dan meminta konfirmasi dua kali.',
    'state_on' => 'Aktif',
    'state_off' => 'Nonaktif',
    'last_changed_by' => 'Terakhir diubah :time UTC oleh :name.',
    'last_changed' => 'Terakhir diubah :time UTC.',
    'never_changed' => 'Belum pernah diubah — aktif secara default.',
    'no_account_relies' => 'Saat ini tidak ada akun yang hanya bergantung pada penyedia ini — setiap akun juga memiliki kata sandi atau cara masuk lain.',
    'would_lock_out' => '{1}Menonaktifkan ini akan mengunci <strong>:count</strong> akun — tanpa kata sandi, dan penyedia ini satu-satunya cara mereka masuk.|[0,*]Menonaktifkan ini akan mengunci <strong>:count</strong> akun — tanpa kata sandi, dan penyedia ini satu-satunya cara mereka masuk.',
    'reason' => 'Alasan',
    'why_turn_off' => 'Mengapa Anda menonaktifkan ini?',
    'why_turn_on' => 'Mengapa Anda mengaktifkan ini?',
    'confirm_lockout_zero' => 'Saya mengerti bahwa siapa pun yang hanya masuk dengan cara ini akan terkunci mulai sekarang hingga sakelar ini diaktifkan kembali.',
    'confirm_lockout' => '{1}Saya mengerti bahwa ini mengunci :count akun hingga diaktifkan kembali.|[0,*]Saya mengerti bahwa ini mengunci :count akun hingga diaktifkan kembali.',
    'turn_off' => 'Nonaktifkan',
    'turn_on' => 'Aktifkan',
];
