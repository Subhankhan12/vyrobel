<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Seule une option peut être confirmée — cette réservation est au statut « :status ».',
    'hold_confirmed' => 'Option confirmée — la réservation est désormais un séjour confirmé.',
    'cannot_cancel_closed' => 'Cette réservation ne peut pas être annulée — elle est déjà clôturée.',
    'reason_too_long' => 'Ce motif d’annulation est trop long — 255 caractères maximum.',
    'cancel_rejected' => 'Cette réservation n’a pas pu être annulée — le service de réservation a refusé l’opération. Rien n’a été modifié.',
    'cancelled_refunded' => 'Réservation annulée et remboursement de :amount effectué (dans le délai d’annulation gratuite). Les nuits qu’elle occupait sont de nouveau libres dans le calendrier.',
    'cancelled' => 'Réservation annulée. Les nuits qu’elle occupait sont de nouveau libres dans le calendrier.',
    'cannot_no_show' => 'Cette réservation ne peut pas être marquée comme no-show — seule une réservation confirmée peut l’être.',
    'no_show_rejected' => 'Cette réservation n’a pas pu être marquée comme no-show — le service de réservation a refusé l’opération. Rien n’a été modifié.',
    'marked_no_show' => 'Réservation marquée comme no-show. Les nuits qu’elle occupait sont de nouveau libres dans le calendrier.',
];
