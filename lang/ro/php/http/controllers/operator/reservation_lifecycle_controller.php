<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Doar o opțiune poate fi confirmată — această rezervare are statusul „:status”.',
    'hold_confirmed' => 'Opțiunea a fost confirmată — rezervarea este acum un sejur confirmat.',
    'cannot_cancel_closed' => 'Rezervarea nu poate fi anulată — este deja închisă.',
    'reason_too_long' => 'Motivul anulării este prea lung — maximum 255 de caractere.',
    'cancel_rejected' => 'Rezervarea nu a putut fi anulată — serviciul de rezervări a refuzat operațiunea. Nu s-a modificat nimic.',
    'cancelled_refunded' => 'Rezervarea a fost anulată și s-a rambursat suma de :amount (în perioada de anulare gratuită). Nopțile reținute sunt din nou disponibile în calendar.',
    'cancelled' => 'Rezervarea a fost anulată. Nopțile reținute sunt din nou disponibile în calendar.',
    'cannot_no_show' => 'Rezervarea nu poate fi marcată ca neprezentare — doar o rezervare confirmată poate fi.',
    'no_show_rejected' => 'Rezervarea nu a putut fi marcată ca neprezentare — serviciul de rezervări a refuzat operațiunea. Nu s-a modificat nimic.',
    'marked_no_show' => 'Rezervarea a fost marcată ca neprezentare. Nopțile reținute sunt din nou disponibile în calendar.',
];
