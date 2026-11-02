<?php

// I18N-01 — it translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Si può confermare solo una prenotazione in opzione: questa prenotazione ha lo stato «:status».',
    'hold_confirmed' => 'Opzione confermata: la prenotazione è ora un soggiorno confermato.',
    'cannot_cancel_closed' => 'Impossibile cancellare la prenotazione: è già chiusa.',
    'reason_too_long' => 'Il motivo della cancellazione è troppo lungo: massimo 255 caratteri.',
    'cancel_rejected' => 'Impossibile cancellare la prenotazione: il servizio prenotazioni l’ha rifiutata. Non è stato modificato nulla.',
    'cancelled_refunded' => 'Prenotazione cancellata, rimborso: :amount (entro il periodo di cancellazione gratuita). Le notti occupate sono di nuovo libere nel calendario.',
    'cancelled' => 'Prenotazione cancellata. Le notti occupate sono di nuovo libere nel calendario.',
    'cannot_no_show' => 'Impossibile segnare la prenotazione come no-show: è possibile solo per una prenotazione confermata.',
    'no_show_rejected' => 'Impossibile segnare la prenotazione come no-show: il servizio prenotazioni l’ha rifiutata. Non è stato modificato nulla.',
    'marked_no_show' => 'Prenotazione segnata come no-show. Le notti occupate sono di nuovo libere nel calendario.',
];
