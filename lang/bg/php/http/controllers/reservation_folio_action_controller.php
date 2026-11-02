<?php

// I18N-01 — bg translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Плащанията с карта минават през Stripe (тестов режим) – ще са достъпни след старта на реалната работа. Запишете плащане в брой или банков превод, за да приемете плащане на рецепцията още сега.',
    'payment_amount_invalid' => 'Въведете сума на плащането, по-голяма от нула.',
    'payment_recorded' => 'Плащането е записано.',
    'charge_amount_invalid' => 'Въведете сума на начислението, по-голяма от нула.',
    'charge_added' => 'Начислението е добавено.',
    'refund_only_settled' => 'Може да бъде възстановена сума само по получено плащане.',
    'already_fully_refunded' => 'Сумата по това плащане вече е възстановена изцяло.',
    'refund_amount_invalid' => 'Въведете сума за възстановяване, по-голяма от нула.',
    'refund_exceeds' => 'Възстановяването е отхвърлено – по това плащане все още могат да бъдат възстановени само :amount :currency.',
    'refund_issued' => 'Сумата е възстановена.',
    'billed_to_account' => 'Фактурирано на сметка :account. Салдото остава дължимо, докато не бъде платено.',
    'returned_to_guest' => 'Върнато във фолиото на госта.',
];
