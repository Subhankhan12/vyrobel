<?php

// I18N-01 — fr translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Ce motif de check-out forcé n’est pas reconnu.',
    'override_note_required' => 'Un check-out forcé avec le motif « Autre » nécessite une note expliquant ce qui s’est passé.',
    'refused_confirmed' => 'Impossible de faire le check-out d’une réservation confirmée : le check-in d’un séjour doit être fait avant son check-out. Si le client a séjourné et part plus tôt, faites d’abord son check-in, puis son check-out. S’il n’est jamais arrivé, marquez plutôt la réservation en no-show.',
    'refused_closed' => 'Impossible de faire le check-out d’une réservation au statut « :status » : la réservation est clôturée et aucun séjour n’a eu lieu. Si le client s’est finalement présenté, créez une nouvelle réservation.',
    'refused_other' => 'Impossible de faire le check-out d’une réservation au statut « :status ».',
];
