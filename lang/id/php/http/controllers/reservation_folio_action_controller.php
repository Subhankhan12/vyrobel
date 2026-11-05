<?php

// I18N-01 — id translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Pembayaran kartu diproses melalui Stripe (mode tes) — tersedia setelah beralih ke kunci live. Catat pembayaran tunai atau transfer bank untuk menerima pembayaran di resepsionis sekarang.',
    'payment_amount_invalid' => 'Masukkan jumlah pembayaran lebih dari nol.',
    'payment_recorded' => 'Pembayaran berhasil dicatat.',
    'charge_amount_invalid' => 'Masukkan jumlah tagihan lebih dari nol.',
    'charge_added' => 'Tagihan berhasil ditambahkan.',
    'refund_only_settled' => 'Hanya pembayaran yang sudah berhasil yang dapat dikembalikan dananya.',
    'already_fully_refunded' => 'Dana pembayaran tersebut sudah dikembalikan sepenuhnya.',
    'refund_amount_invalid' => 'Masukkan jumlah pengembalian dana lebih dari nol.',
    'refund_exceeds' => 'Pengembalian dana ditolak — hanya :amount :currency yang masih dapat dikembalikan dari pembayaran tersebut.',
    'refund_issued' => 'Pengembalian dana berhasil diproses.',
    'billed_to_account' => 'Ditagihkan ke :account. Saldo tetap terutang hingga akun tersebut membayar.',
    'returned_to_guest' => 'Dikembalikan ke folio tamu.',
];
