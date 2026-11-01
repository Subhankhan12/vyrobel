<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Choisissez un plan tarifaire.',
    'name_required' => 'Donnez un nom à la saison.',
    'name_too_long' => 'Le nom de la saison ne doit pas dépasser 80 caractères.',
    'choose_first_night' => 'Choisissez la première nuit.',
    'choose_last_night' => 'Choisissez la dernière nuit.',
    'last_before_first' => 'La dernière nuit ne peut pas précéder la première.',
    'price_required' => 'Saisissez un prix par nuit.',
    'read_only' => 'Votre organisation est en lecture seule pour le moment — les modifications de tarifs sont suspendues.',
    'price_out_of_range' => 'Saisissez un prix par nuit supérieur à zéro et ne dépassant pas 1 000 000.',
    'season_saved' => 'Saison enregistrée. Vérifiez son impact, puis appliquez-la pour mettre à jour les prix de ces nuits.',
    'already_applied' => 'Cette saison a déjà été appliquée et ne peut donc plus être modifiée — les tarifs par nuit qu’elle a appliqués ont remplacé les précédents, et aucun enregistrement des anciens prix n’a été conservé. Créez plutôt une nouvelle saison sur ces nuits.',
    'season_updated' => 'Saison mise à jour. Vérifiez son impact, puis appliquez-la pour mettre à jour les prix de ces nuits.',
    'deleted_after_apply' => 'Saison « :season » supprimée. ⚠️ Les tarifs par nuit qu’elle a déjà appliqués restent inchangés — supprimer une saison ne rétablit pas les prix qu’elle a remplacés. Fixez vous-même le prix de ces nuits s’il est erroné.',
    'deleted_not_applied' => 'Saison « :season » supprimée. Elle n’avait pas été appliquée : aucun tarif n’a changé.',
    'applied' => 'Saison « :season » appliquée — réécriture de :nights sur :units.',
    'nights_count' => '{1} :count nuit|[0,*] :count nuits',
    'units_count' => '{1} :count unité|[0,*] :count unités',
    'overlapping_reapplied' => '{1} :count saison chevauchante a été réappliquée selon l’ordre de priorité.|[0,*] :count saisons chevauchantes ont été réappliquées selon l’ordre de priorité.',
    'pick_unit_to_continue' => 'Choisissez une unité pour continuer.',
    'stay_nights_unpriced' => '{1} :count nuit du séjour n’a toujours pas de prix.|[0,*] :count nuits du séjour n’ont toujours pas de prix.',
];
