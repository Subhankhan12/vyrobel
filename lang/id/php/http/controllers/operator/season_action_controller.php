<?php

// I18N-01 — id translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Pilih paket tarif.',
    'name_required' => 'Beri nama musim ini.',
    'name_too_long' => 'Nama musim maksimal 80 karakter.',
    'choose_first_night' => 'Pilih malam pertama.',
    'choose_last_night' => 'Pilih malam terakhir.',
    'last_before_first' => 'Malam terakhir tidak boleh sebelum malam pertama.',
    'price_required' => 'Masukkan harga per malam.',
    'read_only' => 'Organisasi Anda saat ini hanya baca — perubahan tarif dijeda.',
    'price_out_of_range' => 'Masukkan harga per malam lebih dari nol dan tidak lebih dari 1.000.000.',
    'season_saved' => 'Musim disimpan. Tinjau cakupan dampaknya, lalu terapkan untuk menulis harga pada malam tersebut.',
    'already_applied' => 'Musim ini sudah diterapkan, jadi tidak dapat diubah — tarif per malam yang ditulisnya sudah menggantikan tarif sebelumnya, dan harga lama tidak tercatat. Buat musim baru untuk malam tersebut.',
    'season_updated' => 'Musim diperbarui. Tinjau cakupan dampaknya, lalu terapkan untuk menulis harga pada malam tersebut.',
    'deleted_after_apply' => 'Musim “:season” dihapus. ⚠️ Tarif per malam yang sudah diterapkannya tidak berubah — menghapus musim tidak memulihkan harga yang digantikannya. Atur sendiri harga malam tersebut jika salah.',
    'deleted_not_applied' => 'Musim “:season” dihapus. Musim ini belum diterapkan, jadi tidak ada tarif yang berubah.',
    'applied' => 'Musim :season diterapkan — :nights ditulis ulang di :units.',
    'nights_count' => '{1}:count malam|[0,*]:count malam',
    'units_count' => '{1}:count unit|[0,*]:count unit',
    'overlapping_reapplied' => '{1}:count musim tumpang tindih diterapkan ulang sesuai urutan prioritas.|[0,*]:count musim tumpang tindih diterapkan ulang sesuai urutan prioritas.',
    'pick_unit_to_continue' => 'Pilih unit untuk melanjutkan.',
    'stay_nights_unpriced' => '{1}:count malam dari masa inap ini masih belum memiliki harga.|[0,*]:count malam dari masa inap ini masih belum memiliki harga.',
];
