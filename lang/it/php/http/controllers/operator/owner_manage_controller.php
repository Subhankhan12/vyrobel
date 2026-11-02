<?php

// I18N-01 — it translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Inserire la commissione, in percentuale.',
    'commission_range' => 'La commissione deve essere un numero compreso tra 0 e 100.',
    'commission_unchanged' => 'Nessuna modifica: la commissione di :name è già :rate (decorrenza: :date).',
    'commission_from_today' => 'La commissione di :name è :rate a partire da oggi: non c’era alcun contratto in vigore.',
    'commission_waiting_cancelled' => 'La commissione di :name resta :rate: la modifica prevista (decorrenza: :date) non verrà applicata.',
    'commission_from_date' => 'La commissione di :name è :rate (decorrenza: :date) per le prenotazioni calcolate da ora in poi. Le prenotazioni già calcolate mantengono la commissione applicata.',
    'payouts_paused' => 'Versamenti sospesi per :name: nessun versamento verrà effettuato finché non saranno ripresi.',
    'payouts_resumed' => 'Versamenti ripresi per :name.',
    'period_start_required' => 'Scegliere il primo giorno del periodo.',
    'period_end_required' => 'Scegliere l’ultimo giorno del periodo.',
    'period_end_after_start' => 'Il periodo deve terminare lo stesso giorno in cui inizia o in un giorno successivo.',
    'period_not_closed' => 'Impossibile chiudere il periodo: :reason Scegliere un periodo che inizi dopo l’ultimo periodo chiuso.',
    'period_closed' => 'Periodo chiuso — rendiconto n. :number finalizzato, importo da versare: :amount. I ricavi non ancora incassati dagli ospiti restano non attribuiti e compariranno in un rendiconto successivo.',
    'note_required' => 'Scrivere prima la nota.',
    'note_max' => 'Una nota può contenere al massimo 2.000 caratteri.',
    'note_added' => 'Nota aggiunta.',
    'remove_password_required' => 'Inserire la Sua password per rimuovere questo proprietario.',
    'password_wrong' => 'La password non è corretta: inserire quella usata per accedere.',
    'confirm_name_required' => 'Digitare il nome del proprietario per confermare.',
    'confirm_name_mismatch' => 'Il nome digitato non corrisponde: il proprietario non è stato rimosso.',
    'owner_removed' => 'Proprietario rimosso: :name. Le unità non sono più assegnate; tutti i rendiconti e i versamenti sono conservati per la contabilità.',
    'workspace_paused_until' => 'Organizzazione in pausa — le modifiche ai proprietari sono in sola lettura (ripresa: :date).',
    'workspace_paused_for_now' => 'Organizzazione in pausa — per il momento le modifiche ai proprietari sono in sola lettura.',
];
