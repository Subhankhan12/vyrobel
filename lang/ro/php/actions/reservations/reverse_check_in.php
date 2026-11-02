<?php

// I18N-01 — ro translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Sejurul revine la statusul „Confirmată”, iar camera rămâne rezervată pentru aceleași nopți.',
    'history_kept' => 'Check-in-ul în sine rămâne în istoricul rezervării. A avut loc, iar evidența arată acest lucru — anularea lui este adăugată alături.',
    'charges_kept' => '{1}:count debit înregistrat după check-in, în valoare totală de :amount, rămâne pe nota de plată. Anularea unei sosiri nu anulează banii cheltuiți — eliminați-l din nota de plată dacă și el a fost o greșeală.|:count debite înregistrate după check-in, în valoare totală de :amount, rămân pe nota de plată. Anularea unei sosiri nu anulează banii cheltuiți — eliminați-le din nota de plată dacă și ele au fost o greșeală.|:count de debite înregistrate după check-in, în valoare totală de :amount, rămân pe nota de plată. Anularea unei sosiri nu anulează banii cheltuiți — eliminați-le din nota de plată dacă și ele au fost o greșeală.',
    'online_check_in_kept' => 'Check-in-ul online făcut de oaspete rămâne finalizat. Oaspetele chiar l-a completat, iar această acțiune nu îi cere să îl completeze din nou.',
    'already_reversed' => 'Acest check-in a fost deja anulat acum câteva clipe — sejurul a revenit la statusul „Confirmată”.',
    'refused_not_checked_in' => 'Check-in-ul se poate anula doar pentru un sejur cu check-in efectuat; acesta are statusul „:status”.',
];
