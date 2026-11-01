<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/reservation_create_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'workspace_paused' => 'Votre organisation est en pause (lecture seule) — les nouvelles réservations sont désactivées jusqu’à la reprise. Reprenez depuis la page « Facturation ».',
    'invalid_dates' => 'Choisissez des dates d’arrivée et de départ valides.',
    'too_many_nights' => 'Une réservation peut compter au maximum :max nuits — choisissez un séjour plus court.',
    'choose_room_and_unit' => 'Choisissez une chambre et une unité précise pour le séjour.',
    'room_type_unavailable' => 'Ce type de chambre n’est pas disponible.',
    'room_not_free_or_unpriced' => 'Cette chambre n’est pas libre, ou n’a pas de prix pour chacune des nuits de ces dates. Choisissez de nouveau une unité dans la liste.',
    'unit_no_longer_free' => 'Cette unité n’est plus libre à ces dates — choisissez-en une autre.',
    'card_coming_soon' => 'Le paiement par carte arrive bientôt — encaissez en espèces ou par virement bancaire maintenant, ou enregistrez le paiement plus tard depuis le folio.',
    'no_payment_permission' => 'Vous n’avez pas l’autorisation d’enregistrer un paiement.',
    'invalid_payment_amount' => 'Saisissez un montant de paiement valide, ex. 150 ou 150,00.',
    'choose_override_reason' => 'Choisissez un motif dans la liste pour réserver quand même.',
    'override_note_required' => 'Précisez le motif dans la note — c’est obligatoire lorsque vous choisissez « Autre ».',
    'dates_restricted' => 'Cette réservation n’a pas été créée — ces dates font l’objet de restrictions. Réservez quand même en indiquant un motif ci-dessous, ou modifiez les dates à l’étape 1.',
    'unit_just_taken' => 'Cette unité vient d’être réservée pour ces dates — aucune réservation n’a été créée. Choisissez-en une autre.',
    'reservation_created' => 'Réservation :reference créée.',
    'credit_applied' => 'Crédit de :amount appliqué.',
    'recorded_as_paid' => 'Paiement de :amount enregistré comme réglé.',
    'walk_in_checked_in' => 'Réservation walk-in :reference : check-in effectué.',
    'walk_in_created' => 'Réservation walk-in :reference créée.',
    'guest_not_found' => 'Ce client est introuvable — recherchez-le de nouveau.',
    'choose_or_name_guest' => 'Choisissez un client existant, ou saisissez un nom pour un nouveau client.',
    'invalid_guest_email' => 'L’adresse e-mail du client n’est pas valide.',
];
