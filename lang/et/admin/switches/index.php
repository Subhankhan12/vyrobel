<?php

// I18N-01 — et translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Hädalülitid',
    'subtitle' => 'Peatage uued registreerumised või ühe teenusepakkuja kaudu sisselogimine ilma juurutuseta. Iga lülitamine auditeeritakse koos põhjusega.',
    'explainer' => 'Iga lüliti on <strong>sees</strong>, kui keegi seda siin välja ei lülita — ka kohe pärast juurutust. Ükski neist ei mõjuta seda konsooli. Teenusepakkujaga <strong>sisselogimise</strong> väljalülitamine lukustab välja kõik, kelle ainus sissepääs on see teenusepakkuja, seega näitavad need lülitid arvu ja küsivad kaks korda.',
    'state_on' => 'Sees',
    'state_off' => 'Väljas',
    'last_changed_by' => 'Viimati muudetud :time UTC, muutja: :name.',
    'last_changed' => 'Viimati muudetud :time UTC.',
    'never_changed' => 'Pole kunagi muudetud — vaikimisi sees.',
    'no_account_relies' => 'Praegu ei sõltu ükski konto ainult sellest teenusepakkujast — igal kontol on ka parool või muu sissepääs.',
    'would_lock_out' => '{1}Väljalülitamine lukustaks välja <strong>:count</strong> konto — parooli pole ja see teenusepakkuja on selle ainus sissepääs.|[0,*]Väljalülitamine lukustaks välja <strong>:count</strong> kontot — parooli pole ja see teenusepakkuja on nende ainus sissepääs.',
    'reason' => 'Põhjus',
    'why_turn_off' => 'Miks te selle välja lülitate?',
    'why_turn_on' => 'Miks te selle sisse lülitate?',
    'confirm_lockout_zero' => 'Saan aru, et kõik, kes logivad sisse ainult sel viisil, lukustatakse välja, kuni lüliti on taas sees.',
    'confirm_lockout' => '{1}Saan aru, et see lukustab :count konto välja, kuni lüliti uuesti sisse lülitatakse.|[0,*]Saan aru, et see lukustab :count kontot välja, kuni lüliti uuesti sisse lülitatakse.',
    'turn_off' => 'Lülita välja',
    'turn_on' => 'Lülita sisse',
];
