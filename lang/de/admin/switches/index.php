<?php

// I18N-01 — de translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Notschalter',
    'subtitle' => 'Neue Registrierungen oder die Anmeldung über einen Anbieter stoppen, ohne Deployment. Jedes Umschalten wird mit Grund protokolliert.',
    'explainer' => 'Jeder Schalter ist <strong>an</strong>, solange ihn hier niemand ausschaltet — auch direkt nach einem Deployment. Keiner davon kann diese Konsole beeinflussen. Wer die <strong>Anmeldung</strong> über einen Anbieter ausschaltet, sperrt alle aus, deren einziger Zugang dieser Anbieter ist — darum zeigen diese Schalter die Anzahl und fragen zweimal nach.',
    'state_on' => 'An',
    'state_off' => 'Aus',
    'last_changed_by' => 'Zuletzt geändert :time UTC von :name.',
    'last_changed' => 'Zuletzt geändert :time UTC.',
    'never_changed' => 'Nie geändert — standardmäßig an.',
    'no_account_relies' => 'Derzeit ist kein Konto allein auf diesen Anbieter angewiesen — jedes hat auch ein Passwort oder einen anderen Zugang.',
    'would_lock_out' => '{1} Wenn Sie dies ausschalten, wird <strong>:count</strong> Konto ausgesperrt — kein Passwort, und dieser Anbieter ist sein einziger Zugang.|[0,*] Wenn Sie dies ausschalten, werden <strong>:count</strong> Konten ausgesperrt — kein Passwort, und dieser Anbieter ist ihr einziger Zugang.',
    'reason' => 'Grund',
    'why_turn_off' => 'Warum schalten Sie dies aus?',
    'why_turn_on' => 'Warum schalten Sie dies ein?',
    'confirm_lockout_zero' => 'Mir ist bewusst, dass alle, die sich nur auf diese Weise anmelden, ab jetzt ausgesperrt sind, bis der Schalter wieder an ist.',
    'confirm_lockout' => '{1} Mir ist bewusst, dass dies :count Konto aussperrt, bis der Schalter wieder eingeschaltet wird.|[0,*] Mir ist bewusst, dass dies :count Konten aussperrt, bis der Schalter wieder eingeschaltet wird.',
    'turn_off' => 'Ausschalten',
    'turn_on' => 'Einschalten',
];
