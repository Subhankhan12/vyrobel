<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Płatności kartą przechodzą przez Stripe (tryb testowy) – będą dostępne po włączeniu trybu na żywo. Aby teraz przyjąć płatność na miejscu, zarejestruj gotówkę lub przelew bankowy.',
    'payment_amount_invalid' => 'Wpisz kwotę płatności większą od zera.',
    'payment_recorded' => 'Zarejestrowano płatność.',
    'charge_amount_invalid' => 'Wpisz kwotę obciążenia większą od zera.',
    'charge_added' => 'Dodano obciążenie.',
    'refund_only_settled' => 'Zwrot jest możliwy tylko dla rozliczonej płatności.',
    'already_fully_refunded' => 'Ta płatność została już w całości zwrócona.',
    'refund_amount_invalid' => 'Wpisz kwotę zwrotu większą od zera.',
    'refund_exceeds' => 'Odmowa zwrotu – z tej płatności można jeszcze zwrócić tylko :amount :currency.',
    'refund_issued' => 'Wykonano zwrot.',
    'billed_to_account' => 'Obciążono konto firmowe: :account. Saldo pozostaje do zapłaty, dopóki firma go nie ureguluje.',
    'returned_to_guest' => 'Przywrócono do folio gościa.',
];
