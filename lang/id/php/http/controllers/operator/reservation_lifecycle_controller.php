<?php

// I18N-01 — id translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Hanya reservasi tentatif yang dapat dikonfirmasi — reservasi ini berstatus “:status”.',
    'hold_confirmed' => 'Reservasi tentatif berhasil dikonfirmasi — kini menjadi masa inap yang dikonfirmasi.',
    'cannot_cancel_closed' => 'Reservasi tersebut tidak dapat dibatalkan — reservasi ini sudah ditutup.',
    'reason_too_long' => 'Alasan pembatalan terlalu panjang — maksimal 255 karakter.',
    'cancel_rejected' => 'Reservasi tersebut tidak dapat dibatalkan — layanan reservasi menolaknya. Tidak ada yang diubah.',
    'cancelled_refunded' => 'Reservasi dibatalkan + dana :amount dikembalikan (dalam batas waktu pembatalan gratis). Malam yang dicadangkan sudah dilepas kembali ke kalender.',
    'cancelled' => 'Reservasi dibatalkan. Malam yang dicadangkan sudah dilepas kembali ke kalender.',
    'cannot_no_show' => 'Reservasi tersebut tidak dapat ditandai sebagai no-show — hanya reservasi yang dikonfirmasi yang dapat ditandai.',
    'no_show_rejected' => 'Reservasi tersebut tidak dapat ditandai sebagai no-show — layanan reservasi menolaknya. Tidak ada yang diubah.',
    'marked_no_show' => 'Ditandai sebagai no-show. Malam yang dicadangkan sudah dilepas kembali ke kalender.',
];
