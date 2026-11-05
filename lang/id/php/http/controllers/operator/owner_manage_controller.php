<?php

// I18N-01 — id translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Masukkan komisi, dalam persen.',
    'commission_range' => 'Komisi harus berupa angka antara 0 dan 100.',
    'commission_unchanged' => 'Tidak ada yang berubah — komisi :name mulai :date memang sudah :rate.',
    'commission_from_today' => 'Komisi :name menjadi :rate mulai hari ini — sebelumnya belum ada perjanjian yang berlaku.',
    'commission_waiting_cancelled' => 'Komisi :name tetap :rate — perubahan yang dijadwalkan pada :date tidak akan dijalankan.',
    'commission_from_date' => 'Komisi :name menjadi :rate mulai :date — untuk reservasi yang dihitung mulai sekarang. Reservasi yang sudah dihitung tetap memakai komisi lamanya.',
    'payouts_paused' => 'Pencairan untuk :name dijeda — tidak ada pencairan yang dikirim kepada pemilik ini hingga Anda melanjutkannya.',
    'payouts_resumed' => 'Pencairan untuk :name dilanjutkan.',
    'period_start_required' => 'Pilih hari pertama periode.',
    'period_end_required' => 'Pilih hari terakhir periode.',
    'period_end_after_start' => 'Periode harus berakhir pada atau setelah hari mulainya.',
    'period_not_closed' => 'Periode tersebut tidak dapat ditutup: :reason Pilih periode yang dimulai setelah periode terakhir yang sudah dikunci.',
    'period_sealed' => 'Rekap yang sudah final atau terkunci tidak dapat dibuat ulang.',
    'period_not_closed_other' => 'Rekap untuk periode tersebut tidak dapat dibuat.',
    'period_closed' => 'Periode ditutup — rekap No. :number difinalkan dengan bersih dibayarkan :amount. Pendapatan yang belum diterima dari tamu belum dihitung dan akan muncul di rekap berikutnya.',
    'note_required' => 'Tulis catatannya terlebih dahulu.',
    'note_max' => 'Catatan maksimal 2.000 karakter.',
    'note_added' => 'Catatan berhasil ditambahkan.',
    'remove_password_required' => 'Masukkan kata sandi Anda untuk mengarsipkan pemilik ini.',
    'password_wrong' => 'Kata sandi salah — masukkan kata sandi yang Anda gunakan untuk masuk.',
    'confirm_name_required' => 'Ketik nama pemilik untuk konfirmasi.',
    'confirm_name_mismatch' => 'Nama yang Anda ketik tidak cocok — pemilik tidak diarsipkan.',
    'owner_removed' => ':name diarsipkan — unit dilepas dari pemilik ini, semua rekap & pencairan tetap disimpan untuk akuntansi.',
    'workspace_paused_until' => 'Organisasi dijeda — perubahan data pemilik hanya baca hingga :date.',
    'workspace_paused_for_now' => 'Organisasi dijeda — perubahan data pemilik hanya baca untuk sementara.',
];
