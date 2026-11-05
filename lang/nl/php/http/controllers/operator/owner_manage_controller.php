<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Voer de commissie in, als percentage.',
    'commission_range' => 'De commissie moet een getal tussen 0 en 100 zijn.',
    'commission_unchanged' => 'Er is niets gewijzigd — :rate is al de commissie van :name vanaf :date.',
    'commission_from_today' => 'De commissie van :name is :rate vanaf vandaag — er gold nog geen overeenkomst.',
    'commission_waiting_cancelled' => ':name blijft op :rate — de wijziging die voor :date gepland stond, gaat niet door.',
    'commission_from_date' => 'De commissie van :name is :rate vanaf :date — voor reserveringen die vanaf nu worden berekend. Reserveringen die al zijn berekend, houden hun percentage.',
    'payouts_paused' => 'Uitbetalingen gepauzeerd voor :name — er gaat geen uitbetaling naar deze eigenaar tot u ze hervat.',
    'payouts_resumed' => 'Uitbetalingen hervat voor :name.',
    'period_start_required' => 'Kies de eerste dag van de periode.',
    'period_end_required' => 'Kies de laatste dag van de periode.',
    'period_end_after_start' => 'De periode moet eindigen op of na de dag waarop ze begint.',
    'period_not_closed' => 'Die periode kon niet worden afgesloten: :reason Kies een periode die begint na de laatste afgesloten periode.',
    'period_sealed' => 'Een definitieve of vergrendelde afrekening kan niet opnieuw worden aangemaakt.',
    'period_not_closed_other' => 'De afrekening voor die periode kon niet worden opgebouwd.',
    'period_closed' => 'Periode afgesloten — afrekening nr. :number definitief, uit te betalen: :amount. Omzet die nog niet bij gasten is geïnd, blijft openstaan en verschijnt op een latere afrekening.',
    'note_required' => 'Schrijf eerst de notitie.',
    'note_max' => 'Een notitie mag maximaal 2.000 tekens bevatten.',
    'note_added' => 'Notitie toegevoegd.',
    'remove_password_required' => 'Voer uw wachtwoord in om deze eigenaar weg te halen.',
    'password_wrong' => 'Dat wachtwoord klopt niet — voer het wachtwoord in waarmee u inlogt.',
    'confirm_name_required' => 'Typ ter bevestiging de naam van de eigenaar.',
    'confirm_name_mismatch' => 'De getypte naam komt niet overeen — de eigenaar is niet weggehaald.',
    'owner_removed' => ':name weggehaald — eenheden losgekoppeld, alle afrekeningen en uitbetalingen bewaard voor de boekhouding.',
    'workspace_paused_until' => 'Organisatie gepauzeerd — eigenaarswijzigingen zijn tot :date alleen-lezen.',
    'workspace_paused_for_now' => 'Organisatie gepauzeerd — eigenaarswijzigingen zijn voorlopig alleen-lezen.',
];
