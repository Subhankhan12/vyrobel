<?php

// I18N-01 — id translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Itu bukan alasan check-out dengan sisa tagihan yang dikenali.',
    'override_note_required' => 'Alasan “Lainnya” memerlukan catatan yang menjelaskan apa yang terjadi.',
    'refused_confirmed' => 'Tidak dapat melakukan check-out untuk reservasi yang dikonfirmasi: masa inap harus check-in terlebih dahulu sebelum dapat check-out. Jika tamu sudah menginap dan pergi lebih awal, lakukan check-in terlebih dahulu, lalu check-out. Jika tamu tidak pernah datang, tandai reservasi sebagai no-show.',
    'refused_closed' => 'Tidak dapat melakukan check-out untuk reservasi berstatus “:status”: reservasi sudah ditutup dan tidak ada masa inap yang berlangsung. Tamu yang ternyata datang memerlukan reservasi baru.',
    'refused_other' => 'Tidak dapat melakukan check-out untuk reservasi berstatus “:status”.',
];
