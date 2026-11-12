<?php

// I18N-01 — fr translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Impossible d’attribuer une unité à :owner — cette personne n’a pas encore accepté son invitation, ou a été retirée. Seul un propriétaire actif peut se voir attribuer une unité.',
    'unit_on_pending_invitation' => '« :unit » figure sur une invitation de propriétaire qui n’a pas encore été acceptée. L’unité devient celle de ce propriétaire à l’acceptation de l’invitation — ou annulez d’abord cette invitation pour attribuer l’unité à quelqu’un d’autre.',
    'unit_already_owned' => '« :unit » appartient déjà à :owner : elle ne peut donc pas être attribuée à quelqu’un d’autre ici. Seule une unité sans propriétaire peut être attribuée.',
    'unit_owned_by_another' => 'un autre propriétaire',
    'date_invalid' => 'Choisissez une date valide à partir de laquelle attribuer l’unité.',
    'date_in_past' => 'Choisissez aujourd’hui ou un jour ultérieur. Un jour déjà passé modifierait des revenus déjà calculés.',
    'date_too_far' => 'Choisissez un jour dans les :days prochains jours.',
    'no_agreement' => ':owner n’a aucun contrat de gestion qui couvre « :unit » le :date. Créez d’abord pour :owner un contrat qui couvre cette unité à partir du :date, puis attribuez-lui l’unité.',
    'stay_across_date' => 'Un client séjourne dans « :unit » du :from au :to, à cheval sur le :date. Choisissez le :to ou un jour ultérieur : aucun séjour ne sera alors partagé entre vous et :owner.',
    'future_stays_with_another_owner' => '« :unit » a des séjours à partir du :date déjà calculés pour un autre propriétaire (:owners). Ces séjours lui restent attribués : l’unité ne peut donc pas être attribuée à partir de ce jour.',
];
