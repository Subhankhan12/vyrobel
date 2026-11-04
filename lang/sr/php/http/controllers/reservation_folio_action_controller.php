<?php

// I18N-01 — sr translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Плаћања картицом иду преко система Stripe (тест режим) – биће доступна када се пређе на кључеве за режим уживо. Да бисте сада примили уплату на рецепцији, евидентирајте готовину или банковни трансфер.',
    'payment_amount_invalid' => 'Унесите износ уплате већи од нуле.',
    'payment_recorded' => 'Уплата је евидентирана.',
    'charge_amount_invalid' => 'Унесите износ задужења већи од нуле.',
    'charge_added' => 'Задужење је додато.',
    'refund_only_settled' => 'Повраћај је могућ само за реализовану уплату.',
    'already_fully_refunded' => 'Та уплата је већ у потпуности враћена.',
    'refund_amount_invalid' => 'Унесите износ повраћаја већи од нуле.',
    'refund_exceeds' => 'Повраћај је одбијен – за ту уплату може се вратити још само :amount :currency.',
    'refund_issued' => 'Новац је враћен.',
    'billed_to_account' => 'Пребачено на клијента :account. Салдо остаје неплаћен док клијент не плати.',
    'returned_to_guest' => 'Враћено на фолио госта.',
];
