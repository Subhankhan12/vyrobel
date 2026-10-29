<?php

// I18N-01 — de translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Wählen Sie einen Preisplan.',
    'name_required' => 'Geben Sie der Saison einen Namen.',
    'name_too_long' => 'Der Saisonname darf höchstens 80 Zeichen lang sein.',
    'choose_first_night' => 'Wählen Sie die erste Nacht.',
    'choose_last_night' => 'Wählen Sie die letzte Nacht.',
    'last_before_first' => 'Die letzte Nacht darf nicht vor der ersten Nacht liegen.',
    'price_required' => 'Geben Sie einen Preis pro Nacht ein.',
    'read_only' => 'Ihre Organisation hat gerade nur Lesezugriff — Preisänderungen sind pausiert.',
    'price_out_of_range' => 'Geben Sie einen Preis pro Nacht ein, der größer als null und höchstens 1.000.000 ist.',
    'season_saved' => 'Saison gespeichert. Prüfen Sie die Auswirkungen, und wenden Sie die Saison dann an, um die Preise für diese Nächte zu setzen.',
    'already_applied' => 'Diese Saison wurde bereits angewendet und kann daher nicht bearbeitet werden — die von ihr gesetzten Preise pro Nacht haben die vorherigen ersetzt, und die alten Preise wurden nirgends gespeichert. Legen Sie stattdessen eine neue Saison für diese Nächte an.',
    'season_updated' => 'Saison aktualisiert. Prüfen Sie die Auswirkungen, und wenden Sie die Saison dann an, um die Preise für diese Nächte zu setzen.',
    'deleted_after_apply' => 'Saison „:season“ gelöscht. ⚠️ Die bereits angewendeten Preise pro Nacht bleiben unverändert — das Löschen einer Saison stellt die ersetzten Preise nicht wieder her. Legen Sie die Preise für diese Nächte selbst fest, falls sie falsch sind.',
    'deleted_not_applied' => 'Saison „:season“ gelöscht. Sie war noch nicht angewendet, daher haben sich keine Preise geändert.',
    'applied' => ':season angewendet — :nights in :units überschrieben.',
    'nights_count' => '{1} :count Nacht|[0,*] :count Nächte',
    'units_count' => '{1} :count Einheit|[0,*] :count Einheiten',
    'overlapping_reapplied' => '{1} :count überlappende Saison wurde nach Vorrang erneut angewendet.|[0,*] :count überlappende Saisons wurden nach Vorrang erneut angewendet.',
    'pick_unit_to_continue' => 'Wählen Sie eine Einheit, um fortzufahren.',
    'stay_nights_unpriced' => '{1} :count Nacht des Aufenthalts hat noch keinen Preis.|[0,*] :count Nächte des Aufenthalts haben noch keinen Preis.',
];
