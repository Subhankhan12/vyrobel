<?php

// I18N-01 — fr translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Impossible de faire le check-in d’une réservation annulée : la réservation est clôturée et la chambre a peut-être déjà été revendue. Si le client revient, créez une nouvelle réservation.',
    'refused_no_show' => 'Impossible de faire le check-in d’une réservation en no-show : la réservation est clôturée et ses nuits ont été remises en vente. Pour installer ce client dans une chambre, utilisez le bouton « Nouvelle réservation » de la page « Réception » — la disponibilité est revérifiée avant la réservation. Le no-show et les éventuels frais déjà facturés restent dans la fiche du client.',
    'refused_checked_out' => 'Impossible de faire le check-in d’une réservation dont le check-out a été effectué : le départ a déjà été enregistré et traité. L’annulation d’un check-out n’est pas disponible.',
    'refused_tentative' => 'Impossible de faire le check-in d’une réservation sous option : la chambre est bloquée mais pas confirmée, personne n’a donc encore promis ce séjour au client. Cliquez d’abord sur le bouton « Confirmer l’option » de cette réservation, puis faites le check-in. Les nuits sont déjà bloquées par cette réservation : la confirmation ne prend rien de plus et ne peut pas échouer faute de disponibilité.',
    'refused_other' => 'Impossible de faire le check-in d’une réservation au statut « :status ».',
];
