<?php

// I18N-01 — id translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Tidak dapat melakukan check-in untuk reservasi yang dibatalkan: reservasi sudah ditutup dan kamarnya mungkin sudah dijual kembali. Tamu yang kembali memerlukan reservasi baru.',
    'refused_no_show' => 'Tidak dapat melakukan check-in untuk reservasi no-show: reservasi sudah ditutup dan malamnya sudah dilepas untuk dijual kembali. Untuk menempatkan tamu ini di kamar, gunakan tombol “Walk-in” di Resepsionis — ketersediaan akan diperiksa ulang sebelum reservasi dibuat. No-show tersebut dan biaya apa pun yang sudah ditagihkan atasnya tetap tercatat di data tamu.',
    'refused_checked_out' => 'Tidak dapat melakukan check-in untuk reservasi yang sudah check-out: keberangkatan sudah dicatat dan ditindaklanjuti. Jika check-out tersebut keliru, gunakan “Urungkan check-out” pada reservasi terlebih dahulu.',
    'refused_tentative' => 'Tidak dapat melakukan check-in untuk reservasi tentatif: kamar dicadangkan tetapi belum dikonfirmasi, jadi belum ada yang menjanjikan masa inap ini kepada tamu. Tekan tombol “Konfirmasi reservasi tentatif” pada reservasi ini terlebih dahulu, lalu lakukan check-in. Malamnya sudah dicadangkan oleh reservasi ini, jadi konfirmasi tidak memakai ketersediaan tambahan dan tidak dapat gagal karena ketersediaan.',
    'refused_other' => 'Tidak dapat melakukan check-in untuk reservasi berstatus “:status”.',
];
