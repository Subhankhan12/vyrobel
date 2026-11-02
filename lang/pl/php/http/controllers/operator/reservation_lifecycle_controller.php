<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Potwierdzić można tylko rezerwację wstępną – ta rezerwacja ma status „:status”.',
    'hold_confirmed' => 'Potwierdzono rezerwację wstępną – to teraz potwierdzony pobyt.',
    'cannot_cancel_closed' => 'Nie można anulować tej rezerwacji – jest już zamknięta.',
    'reason_too_long' => 'Powód anulowania jest zbyt długi – maksymalnie 255 znaków.',
    'cancel_rejected' => 'Nie udało się anulować rezerwacji – usługa rezerwacji ją odrzuciła. Nic nie zostało zmienione.',
    'cancelled_refunded' => 'Anulowano rezerwację i zwrócono :amount (w okresie bezpłatnego anulowania). Zajmowane noce znów są dostępne w kalendarzu.',
    'cancelled' => 'Rezerwacja anulowana. Zajmowane noce znów są dostępne w kalendarzu.',
    'cannot_no_show' => 'Nie można oznaczyć tej rezerwacji jako niepojawienie się – dotyczy to tylko potwierdzonych rezerwacji.',
    'no_show_rejected' => 'Nie udało się oznaczyć rezerwacji jako niepojawienie się – usługa rezerwacji ją odrzuciła. Nic nie zostało zmienione.',
    'marked_no_show' => 'Oznaczono jako niepojawienie się. Zajmowane noce znów są dostępne w kalendarzu.',
];
