<?php

// I18N-01 — fr translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'La réservation redevient confirmée, et la chambre reste bloquée pour les mêmes nuits.',
    'history_kept' => 'Le check-in lui-même reste dans l’historique de la réservation. Il a eu lieu, et l’historique le dit — l’annulation y est ajoutée en complément.',
    'charges_kept' => '{1} :count prestation enregistrée depuis le check-in, pour un total de :amount, reste sur le folio. Annuler un check-in ne fait pas disparaître les dépenses — supprimez-la du folio si elle était aussi une erreur.|[0,*] :count prestations enregistrées depuis le check-in, pour un total de :amount, restent sur le folio. Annuler un check-in ne fait pas disparaître les dépenses — supprimez-les du folio si elles étaient aussi une erreur.',
    'online_check_in_kept' => 'Le check-in en ligne fait par le client reste terminé. Il l’a vraiment rempli, et cette action ne lui demande pas de le refaire.',
    'already_reversed' => 'Ce check-in a déjà été annulé il y a un instant — la réservation est de nouveau confirmée.',
    'refused_not_checked_in' => 'Seul un séjour dont le check-in est effectué peut voir son check-in annulé ; celui-ci est au statut « :status ».',
];
