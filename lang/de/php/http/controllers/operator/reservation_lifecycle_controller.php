<?php

// I18N-01 — de translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Nur eine Option kann bestätigt werden — diese Buchung hat den Status „:status“.',
    'hold_confirmed' => 'Option bestätigt — die Buchung ist jetzt ein bestätigter Aufenthalt.',
    'cannot_cancel_closed' => 'Diese Buchung kann nicht storniert werden — sie ist bereits abgeschlossen.',
    'reason_too_long' => 'Dieser Stornierungsgrund ist zu lang — höchstens 255 Zeichen.',
    'cancel_rejected' => 'Diese Buchung konnte nicht storniert werden — der Buchungsdienst hat dies abgelehnt. Es wurde nichts geändert.',
    'cancelled_refunded' => 'Buchung storniert und :amount erstattet (innerhalb der kostenlosen Stornierungsfrist). Die blockierten Nächte sind im Kalender wieder frei.',
    'cancelled' => 'Buchung storniert. Die blockierten Nächte sind im Kalender wieder frei.',
    'cannot_no_show' => 'Diese Buchung kann nicht als No-Show markiert werden — das ist nur bei einer bestätigten Buchung möglich.',
    'no_show_rejected' => 'Diese Buchung konnte nicht als No-Show markiert werden — der Buchungsdienst hat dies abgelehnt. Es wurde nichts geändert.',
    'marked_no_show' => 'Als No-Show markiert. Die blockierten Nächte sind im Kalender wieder frei.',
];
