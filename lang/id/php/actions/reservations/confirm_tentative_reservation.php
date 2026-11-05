<?php

// I18N-01 — id translation of lang/en/php/actions/reservations/confirm_tentative_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_closed' => 'Reservasi berstatus “:status” tidak dapat dikonfirmasi: reservasi ini sudah ditutup dan malamnya sudah dilepas kembali untuk dijual. Tamu yang kembali memerlukan reservasi baru.',
    'refused_checked_in' => 'Reservasi yang sudah check-in tidak dapat dikonfirmasi: tamu sudah tiba, jadi reservasi ini sudah melewati tahap konfirmasi.',
    'refused_checked_out' => 'Reservasi yang sudah check-out tidak dapat dikonfirmasi: masa inapnya sudah berakhir.',
    'refused_enquiry' => 'Reservasi berstatus “Permintaan” tidak dapat dikonfirmasi: hanya reservasi tentatif yang dapat dikonfirmasi.',
    'refused_other' => 'Reservasi berstatus “:status” tidak dapat dikonfirmasi: hanya reservasi tentatif yang dapat dikonfirmasi.',
];
