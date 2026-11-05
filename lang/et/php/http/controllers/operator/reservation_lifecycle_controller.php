<?php

// I18N-01 — et translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Kinnitada saab ainult eelbroneeringut — selle broneeringu olek: :status.',
    'hold_confirmed' => 'Eelbroneering on kinnitatud — broneering on nüüd kinnitatud viibimine.',
    'cannot_cancel_closed' => 'Seda broneeringut ei saa tühistada — see on juba lõppenud.',
    'reason_too_long' => 'See tühistamise põhjus on liiga pikk — kuni 255 märki.',
    'cancel_rejected' => 'Seda broneeringut ei saanud tühistada — broneerimisteenus lükkas selle tagasi. Midagi ei muudetud.',
    'cancelled_refunded' => 'Broneering on tühistatud ja :amount on tagasi makstud (tasuta tühistamise perioodi jooksul). Reserveeritud ööd on kalendris taas vabad.',
    'cancelled' => 'Broneering on tühistatud. Reserveeritud ööd on kalendris taas vabad.',
    'cannot_no_show' => 'Seda broneeringut ei saa märkida kohale mitteilmunuks — seda saab teha ainult kinnitatud broneeringuga.',
    'no_show_rejected' => 'Seda broneeringut ei saanud märkida kohale mitteilmunuks — broneerimisteenus lükkas selle tagasi. Midagi ei muudetud.',
    'marked_no_show' => 'Märgitud kohale mitteilmunuks. Reserveeritud ööd on kalendris taas vabad.',
];
