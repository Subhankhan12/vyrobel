<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Alleen een optie kan worden bevestigd — deze reservering heeft de status “:status”.',
    'hold_confirmed' => 'Optie bevestigd — de reservering is nu een bevestigd verblijf.',
    'cannot_cancel_closed' => 'Die reservering kan niet worden geannuleerd — ze is al afgesloten.',
    'reason_too_long' => 'Die annuleringsreden is te lang — maximaal 255 tekens.',
    'cancel_rejected' => 'Die reservering kon niet worden geannuleerd — de reserveringsservice heeft het geweigerd. Er is niets gewijzigd.',
    'cancelled_refunded' => 'Reservering geannuleerd + :amount terugbetaald (binnen de gratis annuleringstermijn). De vastgehouden nachten staan weer vrij in de kalender.',
    'cancelled' => 'Reservering geannuleerd. De vastgehouden nachten staan weer vrij in de kalender.',
    'cannot_no_show' => 'Die reservering kan niet als no-show worden gemarkeerd — dat kan alleen bij een bevestigde reservering.',
    'no_show_rejected' => 'Die reservering kon niet als no-show worden gemarkeerd — de reserveringsservice heeft het geweigerd. Er is niets gewijzigd.',
    'marked_no_show' => 'Gemarkeerd als no-show. De vastgehouden nachten staan weer vrij in de kalender.',
];
