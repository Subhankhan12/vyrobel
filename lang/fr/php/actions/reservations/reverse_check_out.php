<?php

// I18N-01 — fr translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'La réservation revient au statut « Check-in effectué ».',
    'pending_tasks_removed' => '{1} :count tâche de ménage de départ sera supprimée — elle n’a pas encore commencé.|[0,*] :count tâches de ménage de départ seront supprimées — elles n’ont pas encore commencé.',
    'started_tasks_kept' => '{1} :count tâche de ménage a déjà commencé et ne sera pas modifiée — annuler un travail que quelqu’un a entamé relève du service de ménage, pas d’une annulation d’action.|[0,*] :count tâches de ménage ont déjà commencé et ne seront pas modifiées — annuler un travail que quelqu’un a entamé relève du service de ménage, pas d’une annulation d’action.',
    'unit_stays_dirty' => 'L’unité reste marquée comme sale. Le client est de retour dans la chambre, donc « propre » pourrait ne pas être vrai.',
    'notifications_stay_delivered' => 'Les notifications déjà remises au personnel et au client le restent.',
    'review_unknown' => 'Nous ne savons pas si une demande d’avis a été envoyée pour ce séjour, car son check-out a eu lieu avant que le produit ne commence à les enregistrer. Elle a peut-être été envoyée, ou peut-être pas.',
    'review_sent_on' => 'Une demande d’avis a été envoyée par e-mail au client le :date. Elle ne peut pas être rappelée.',
    'review_sent' => 'Une demande d’avis a été envoyée par e-mail au client. Elle ne peut pas être rappelée.',
    'review_suppressed_by_operator' => 'Aucune demande d’avis n’a été envoyée — l’e-mail de demande d’avis est désactivé pour cet établissement.',
    'review_suppressed_by_guest' => 'Aucune demande d’avis n’a été envoyée — le client a demandé à ne pas recevoir d’e-mails de demande d’avis.',
    'review_not_sent' => 'Aucune demande d’avis n’a été envoyée — il n’y avait pas d’adresse de contact, ou l’envoi a échoué.',
    'refused_not_checked_out' => 'Seul un séjour dont le check-out est effectué peut voir son check-out annulé ; celui-ci est au statut « :status ».',
];
