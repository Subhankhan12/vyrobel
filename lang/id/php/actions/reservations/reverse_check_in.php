<?php

// I18N-01 — id translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Masa inap kembali ke status dikonfirmasi, dan kamar tetap dicadangkan untuk malam yang sama.',
    'history_kept' => 'Check-in itu sendiri tetap tercatat di riwayat reservasi. Check-in itu memang terjadi, dan catatannya menyatakan demikian — pengurungannya ditambahkan di sampingnya.',
    'charges_kept' => '{1}:count tagihan yang dibukukan sejak check-in, dengan total :amount, tetap ada di folio. Mengurungkan kedatangan tidak mengembalikan uang yang sudah dibelanjakan — hapus tagihan tersebut di folio jika tagihan itu juga keliru.|[0,*]:count tagihan yang dibukukan sejak check-in, dengan total :amount, tetap ada di folio. Mengurungkan kedatangan tidak mengembalikan uang yang sudah dibelanjakan — hapus tagihan tersebut di folio jika tagihan itu juga keliru.',
    'online_check_in_kept' => 'Check-in online yang diisi tamu sendiri tetap berstatus selesai. Tamu memang sudah mengisinya, dan tindakan ini tidak meminta mereka mengisinya lagi.',
    'already_reversed' => 'Check-in tersebut sudah diurungkan beberapa saat yang lalu — masa inap sudah kembali ke status dikonfirmasi.',
    'refused_not_checked_in' => 'Check-in hanya dapat diurungkan untuk masa inap yang sudah check-in; masa inap ini berstatus “:status”.',
];
