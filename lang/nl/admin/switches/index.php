<?php

// I18N-01 — nl translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Noodschakelaars',
    'subtitle' => 'Stop nieuwe registraties, of inloggen via één provider, zonder deploy. Elke omzetting wordt met een reden vastgelegd in het auditlogboek.',
    'explainer' => 'Elke schakelaar staat <strong>aan</strong> tenzij iemand hem hier uitzet — ook direct na een deploy. Geen ervan heeft invloed op deze console. Wie <strong>inloggen</strong> via een provider uitzet, sluit iedereen buiten voor wie die provider de enige toegang is; daarom tonen die schakelaars het aantal en vragen ze twee keer om bevestiging.',
    'state_on' => 'Aan',
    'state_off' => 'Uit',
    'last_changed_by' => 'Laatst gewijzigd :time UTC door :name.',
    'last_changed' => 'Laatst gewijzigd :time UTC.',
    'never_changed' => 'Nooit gewijzigd — standaard aan.',
    'no_account_relies' => 'Op dit moment is er geen account dat alleen van deze provider afhangt — elk account heeft ook een wachtwoord of een andere manier om in te loggen.',
    'would_lock_out' => '{1} Als u dit uitzet, wordt <strong>:count</strong> account buitengesloten — geen wachtwoord, en deze provider is de enige toegang.|[0,*] Als u dit uitzet, worden <strong>:count</strong> accounts buitengesloten — geen wachtwoord, en deze provider is hun enige toegang.',
    'reason' => 'Reden',
    'why_turn_off' => 'Waarom zet u dit uit?',
    'why_turn_on' => 'Waarom zet u dit aan?',
    'confirm_lockout_zero' => 'Ik begrijp dat iedereen die alleen op deze manier inlogt, vanaf nu buitengesloten is tot dit weer aan staat.',
    'confirm_lockout' => '{1} Ik begrijp dat dit :count account buitensluit tot het weer wordt aangezet.|[0,*] Ik begrijp dat dit :count accounts buitensluit tot het weer wordt aangezet.',
    'turn_off' => 'Uitzetten',
    'turn_on' => 'Aanzetten',
];
