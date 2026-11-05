<?php

// I18N-01 — id translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Masa inap kembali ke status sudah check-in.',
    'pending_tasks_removed' => '{1}:count tugas pembersihan keberangkatan akan dihapus — belum dimulai.|[0,*]:count tugas pembersihan keberangkatan akan dihapus — belum dimulai.',
    'started_tasks_kept' => '{1}:count tugas pembersihan sudah dimulai dan tidak akan diubah — membatalkan pekerjaan yang sudah dimulai seseorang adalah keputusan housekeeping, bukan bagian dari pengurungan.|[0,*]:count tugas pembersihan sudah dimulai dan tidak akan diubah — membatalkan pekerjaan yang sudah dimulai seseorang adalah keputusan housekeeping, bukan bagian dari pengurungan.',
    'unit_stays_dirty' => 'Unit tetap ditandai kotor. Tamu sudah kembali ke kamar, jadi status “Bersih” mungkin tidak benar.',
    'notifications_stay_delivered' => 'Notifikasi yang sudah terkirim ke staf dan tamu tetap terkirim.',
    'review_unknown' => 'Kami tidak memiliki catatan apakah permintaan ulasan sudah dikirim untuk masa inap ini, karena masa inap ini check-out sebelum produk ini mulai mencatatnya. Permintaan ulasan mungkin sudah dikirim, mungkin juga belum.',
    'review_sent_on' => 'Permintaan ulasan sudah dikirim ke tamu melalui email pada :date. Email ini tidak dapat ditarik kembali.',
    'review_sent' => 'Permintaan ulasan sudah dikirim ke tamu melalui email. Email ini tidak dapat ditarik kembali.',
    'review_suppressed_by_operator' => 'Permintaan ulasan tidak dikirim — properti ini menonaktifkan email permintaan ulasan.',
    'review_suppressed_by_guest' => 'Permintaan ulasan tidak dikirim — tamu meminta untuk tidak menerima email ulasan.',
    'review_not_sent' => 'Permintaan ulasan tidak dikirim — tidak ada alamat kontak, atau pengiriman gagal.',
    'refused_not_checked_out' => 'Check-out hanya dapat diurungkan untuk masa inap yang sudah check-out; masa inap ini berstatus “:status”.',
];
