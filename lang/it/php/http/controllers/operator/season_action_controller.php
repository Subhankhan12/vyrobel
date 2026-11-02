<?php

// I18N-01 — it translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Selezionare un piano tariffario.',
    'name_required' => 'Assegnare un nome alla stagione.',
    'name_too_long' => 'Il nome della stagione può contenere al massimo 80 caratteri.',
    'choose_first_night' => 'Selezionare la prima notte.',
    'choose_last_night' => 'Selezionare l’ultima notte.',
    'last_before_first' => 'L’ultima notte non può precedere la prima.',
    'price_required' => 'Inserire un prezzo per notte.',
    'read_only' => 'La Sua organizzazione è in sola lettura: le modifiche alle tariffe sono sospese.',
    'price_out_of_range' => 'Inserire un prezzo per notte maggiore di zero e non superiore a 1.000.000.',
    'season_saved' => 'Stagione salvata. Verificare cosa cambierebbe, poi applicarla per scrivere i prezzi di quelle notti.',
    'already_applied' => 'Questa stagione è già stata applicata, quindi non può essere modificata: le tariffe per notte che ha scritto hanno sostituito quelle presenti e i prezzi precedenti non sono stati registrati. Creare invece una nuova stagione su quelle notti.',
    'season_updated' => 'Stagione aggiornata. Verificare cosa cambierebbe, poi applicarla per scrivere i prezzi di quelle notti.',
    'deleted_after_apply' => 'Stagione «:season» eliminata. ⚠️ Le tariffe per notte già applicate restano invariate: eliminare una stagione non ripristina i prezzi che ha sostituito. Se sono errati, impostare manualmente quelle notti.',
    'deleted_not_applied' => 'Stagione «:season» eliminata. Non era stata applicata, quindi nessuna tariffa è cambiata.',
    'applied' => 'Stagione «:season» applicata: prezzi riscritti per :nights su :units.',
    'nights_count' => '{1} :count notte|[0,*] :count notti',
    'units_count' => '{1} :count unità|[0,*] :count unità',
    'overlapping_reapplied' => '{1} :count stagione sovrapposta è stata riapplicata in ordine di precedenza.|[0,*] :count stagioni sovrapposte sono state riapplicate in ordine di precedenza.',
    'pick_unit_to_continue' => 'Selezionare un’unità per continuare.',
    'stay_nights_unpriced' => '{1} :count notte del soggiorno non ha ancora un prezzo.|[0,*] :count notti del soggiorno non hanno ancora un prezzo.',
];
