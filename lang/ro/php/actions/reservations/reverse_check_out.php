<?php

// I18N-01 — ro translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Sejurul revine la statusul „Check-in efectuat”.',
    'pending_tasks_removed' => '{1}:count sarcină de curățenie la plecare va fi eliminată — nu a început încă.|:count sarcini de curățenie la plecare vor fi eliminate — nu au început încă.|:count de sarcini de curățenie la plecare vor fi eliminate — nu au început încă.',
    'started_tasks_kept' => '{1}:count sarcină de curățenie a început deja și rămâne neatinsă — anularea unei lucrări începute de cineva este o decizie a personalului de curățenie, nu o simplă revenire.|:count sarcini de curățenie au început deja și rămân neatinse — anularea unei lucrări începute de cineva este o decizie a personalului de curățenie, nu o simplă revenire.|:count de sarcini de curățenie au început deja și rămân neatinse — anularea unei lucrări începute de cineva este o decizie a personalului de curățenie, nu o simplă revenire.',
    'unit_stays_dirty' => 'Unitatea rămâne marcată ca murdară. Oaspetele este din nou în cameră, așa că „curată” s-ar putea să nu mai fie adevărat.',
    'notifications_stay_delivered' => 'Notificările deja livrate personalului și oaspetelui rămân livrate.',
    'review_unknown' => 'Nu avem nicio evidență dacă pentru acest sejur s-a trimis o solicitare de recenzie, deoarece check-out-ul a avut loc înainte ca produsul să înceapă să înregistreze aceste solicitări. Este posibil să fi fost trimisă sau nu.',
    'review_sent_on' => 'Oaspetelui i s-a trimis pe e-mail o solicitare de recenzie pe :date. Aceasta nu mai poate fi retrasă.',
    'review_sent' => 'Oaspetelui i s-a trimis pe e-mail o solicitare de recenzie. Aceasta nu mai poate fi retrasă.',
    'review_suppressed_by_operator' => 'Nu s-a trimis nicio solicitare de recenzie — această proprietate are dezactivat e-mailul de solicitare a recenziilor.',
    'review_suppressed_by_guest' => 'Nu s-a trimis nicio solicitare de recenzie — oaspetele a cerut să nu primească e-mailuri pentru recenzii.',
    'review_not_sent' => 'Nu s-a trimis nicio solicitare de recenzie — nu exista o adresă de contact sau trimiterea a eșuat.',
    'refused_not_checked_out' => 'Check-out-ul se poate anula doar pentru un sejur cu check-out efectuat; acesta are statusul „:status”.',
];
