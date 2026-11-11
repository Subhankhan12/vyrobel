<?php

// I18N-01 — ru translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Оплата картой проходит через Stripe (тестовый режим) и станет доступна после перехода на реальные ключи. Чтобы принять оплату на ресепшене сейчас, внесите наличные или банковский перевод.',
    'payment_amount_invalid' => 'Введите сумму платежа больше нуля.',
    'payment_recorded' => 'Платёж внесён.',
    'charge_amount_invalid' => 'Введите сумму начисления больше нуля.',
    'charge_added' => 'Начисление добавлено.',
    'refund_only_settled' => 'Вернуть можно только проведённые платежи.',
    'already_fully_refunded' => 'Этот платёж уже полностью возвращён.',
    'refund_amount_invalid' => 'Введите сумму возврата больше нуля.',
    'refund_exceeds' => 'Возврат отклонён — по этому платежу можно вернуть ещё только :amount :currency.',
    'refund_issued' => 'Возврат выполнен.',
    'billed_to_account' => 'Счёт выставлен на :account. Баланс остаётся к оплате, пока плательщик его не погасит.',
    'returned_to_guest' => 'Возвращено в фолио гостя.',
    'hold_released' => 'Предавторизация снята — карта гостя освобождена, ничего не списано.',
];
