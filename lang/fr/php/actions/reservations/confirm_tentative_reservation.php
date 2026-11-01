<?php

// I18N-01 — fr translation of lang/en/php/actions/reservations/confirm_tentative_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_closed' => 'Impossible de confirmer une réservation au statut « :status » : la réservation est close et ses nuits ont été remises en vente. Un client qui revient a besoin d’une nouvelle réservation.',
    'refused_checked_in' => 'Impossible de confirmer une réservation dont le check-in est effectué : le client est déjà arrivé, l’étape de la confirmation est donc dépassée.',
    'refused_checked_out' => 'Impossible de confirmer une réservation dont le check-out est effectué : le séjour est déjà terminé.',
    'refused_enquiry' => 'Impossible de confirmer une demande de renseignements : seule une option peut être confirmée.',
    'refused_other' => 'Impossible de confirmer une réservation au statut « :status » : seule une option peut être confirmée.',
];
