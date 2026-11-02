<?php

// I18N-01 — ro translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Întrerupătoare de urgență',
    'subtitle' => 'Opriți înregistrările noi sau conectarea printr-un anumit furnizor, fără o nouă lansare. Fiecare comutare este auditată, cu un motiv.',
    'explainer' => 'Fiecare întrerupător este <strong>activat</strong>, cu excepția cazului în care cineva îl dezactivează aici — inclusiv imediat după o lansare. Niciunul nu poate afecta această consolă. Dezactivarea <strong>conectării</strong> printr-un furnizor blochează pe oricine are acel furnizor ca singură modalitate de acces, așa că aceste întrerupătoare afișează numărul conturilor afectate și cer confirmare de două ori.',
    'state_on' => 'Activat',
    'state_off' => 'Dezactivat',
    'last_changed_by' => 'Ultima modificare: :time UTC, de către :name.',
    'last_changed' => 'Ultima modificare: :time UTC.',
    'never_changed' => 'Nemodificat niciodată — activat implicit.',
    'no_account_relies' => 'Momentan niciun cont nu depinde doar de acest furnizor — fiecare are și o parolă sau altă modalitate de acces.',
    'would_lock_out' => '{1}Dezactivarea ar bloca <strong>:count</strong> cont — fără parolă, iar acest furnizor este singura lui modalitate de acces.|Dezactivarea ar bloca <strong>:count</strong> conturi — fără parolă, iar acest furnizor este singura lor modalitate de acces.|Dezactivarea ar bloca <strong>:count</strong> de conturi — fără parolă, iar acest furnizor este singura lor modalitate de acces.',
    'reason' => 'Motiv',
    'why_turn_off' => 'De ce îl dezactivați?',
    'why_turn_on' => 'De ce îl activați?',
    'confirm_lockout_zero' => 'Înțeleg că oricine se conectează doar în acest mod va fi blocat de acum până la reactivare.',
    'confirm_lockout' => '{1}Înțeleg că astfel se blochează :count cont până la reactivare.|Înțeleg că astfel se blochează :count conturi până la reactivare.|Înțeleg că astfel se blochează :count de conturi până la reactivare.',
    'turn_off' => 'Dezactivare',
    'turn_on' => 'Activare',
];
