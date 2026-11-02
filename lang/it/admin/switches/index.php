<?php

// I18N-01 — it translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Interruttori di emergenza',
    'subtitle' => 'Bloccare le nuove registrazioni, o l’accesso tramite un provider, senza un deploy. Ogni modifica viene registrata con un motivo.',
    'explainer' => 'Ogni interruttore è <strong>attivo</strong> finché qualcuno non lo disattiva qui — anche subito dopo un deploy. Nessuno di essi può influire su questa console. Disattivare l’<strong>accesso</strong> con un provider blocca chiunque abbia quel provider come unico modo di accedere, perciò questi interruttori mostrano il numero di account interessati e chiedono una doppia conferma.',
    'state_on' => 'Attivo',
    'state_off' => 'Disattivato',
    'last_changed_by' => 'Ultima modifica: :time UTC, da :name.',
    'last_changed' => 'Ultima modifica: :time UTC.',
    'never_changed' => 'Mai modificato — attivo per impostazione predefinita.',
    'no_account_relies' => 'Al momento nessun account dipende solo da questo provider — ognuno ha anche una password o un altro modo di accedere.',
    'would_lock_out' => '{1} Disattivarlo bloccherebbe <strong>:count</strong> account — senza password, e questo provider è il suo unico modo di accedere.|[0,*] Disattivarlo bloccherebbe <strong>:count</strong> account — senza password, e questo provider è il loro unico modo di accedere.',
    'reason' => 'Motivo',
    'why_turn_off' => 'Perché lo sta disattivando?',
    'why_turn_on' => 'Perché lo sta attivando?',
    'confirm_lockout_zero' => 'Sono consapevole che chiunque acceda solo in questo modo resterà bloccato da ora fino alla riattivazione.',
    'confirm_lockout' => '{1} Sono consapevole che questa operazione blocca :count account fino alla riattivazione.|[0,*] Sono consapevole che questa operazione blocca :count account fino alla riattivazione.',
    'turn_off' => 'Disattiva',
    'turn_on' => 'Attiva',
];
