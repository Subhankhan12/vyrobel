<?php

// I18N-01 — id translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Koneksi tersebut sudah tidak ada.',
    'unlink_refused_only_way_in' => 'Ditolak — ini satu-satunya cara orang ini dapat masuk (tanpa kata sandi, tanpa penyedia lain). Melepaskannya akan membuat orang ini terkunci selamanya.',
    'unlinked' => 'Penyedia :provider dilepaskan — dibaca ulang: koneksinya sudah tidak ada.',
    'unlink_not_landed' => 'Pelepasan tidak berhasil — koneksinya masih ada.',
    'already_verified' => 'Sudah terverifikasi.',
    'marked_verified' => 'Ditandai terverifikasi — dibaca ulang dari basis data: :stored UTC.',
    'verify_not_landed' => 'Penulisan data tidak berhasil — email_verified_at masih kosong.',
    'already_verified_nothing_to_resend' => 'Sudah terverifikasi — tidak ada yang perlu dikirim ulang.',
    'verification_discarded' => 'TIDAK TERKIRIM — email diatur ke “:mailer”, jadi email verifikasi dibuang. Tidak ada yang menerima apa pun. Pastikan identitas mereka dengan cara lain, lalu gunakan “Tandai email terverifikasi”.',
    'verification_handed' => 'Email verifikasi diserahkan ke layanan email (:mailer).',
    'no_reset_link' => 'Tidak ada tautan atur ulang yang dibuat: :status',
    'reset_discarded' => 'TIDAK TERKIRIM — tautan atur ulang sudah dibuat, tetapi email diatur ke “:mailer”, jadi emailnya dibuang. Tidak ada yang menerima apa pun.',
    'reset_handed' => 'Email atur ulang kata sandi diserahkan ke layanan email (:mailer).',
    'verification_not_sent_undeliverable' => 'TIDAK DIKIRIM — alamat ini tidak akan pernah dapat menerima email (:reason), jadi email verifikasi tidak diserahkan ke layanan email. Tidak ada yang menerima apa pun.',
    'reset_not_sent_undeliverable' => 'TIDAK DIKIRIM — alamat ini tidak akan pernah dapat menerima email (:reason), jadi tautan atur ulang tidak diserahkan ke layanan email. Tidak ada yang menerima apa pun.',
];
