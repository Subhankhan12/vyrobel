<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Introduceți comisionul, în procente.',
    'commission_range' => 'Comisionul trebuie să fie un număr între 0 și 100.',
    'commission_unchanged' => 'Nimic nu s-a modificat — :rate este deja comisionul proprietarului :name din :date.',
    'commission_from_today' => 'Comisionul proprietarului :name este :rate începând de azi — nu exista niciun contract în vigoare.',
    'commission_waiting_cancelled' => 'Proprietarul :name rămâne la :rate — modificarea programată pentru :date nu va mai avea loc.',
    'commission_from_date' => 'Comisionul proprietarului :name este :rate din :date — pentru rezervările ale căror câștiguri se calculează de acum înainte. Rezervările deja calculate își păstrează comisionul.',
    'payouts_paused' => 'Viramentele către proprietarul :name au fost puse în pauză — nu i se trimite niciun virament până la reluare.',
    'payouts_resumed' => 'Viramentele către proprietarul :name au fost reluate.',
    'period_start_required' => 'Alegeți prima zi a perioadei.',
    'period_end_required' => 'Alegeți ultima zi a perioadei.',
    'period_end_after_start' => 'Perioada trebuie să se încheie în ziua în care începe sau după aceasta.',
    'period_not_closed' => 'Perioada nu a putut fi închisă: :reason Alegeți o perioadă care începe după ultima perioadă închisă.',
    'period_sealed' => 'Un decont finalizat sau blocat nu poate fi regenerat.',
    'period_not_closed_other' => 'Decontul pentru acea perioadă nu a putut fi generat.',
    'period_closed' => 'Perioadă închisă — decontul nr. :number a fost finalizat, net de virat: :amount. Veniturile încă neîncasate de la oaspeți nu sunt incluse deocamdată și vor apărea într-un decont ulterior.',
    'note_required' => 'Scrieți mai întâi nota.',
    'note_max' => 'O notă poate avea cel mult 2.000 de caractere.',
    'note_added' => 'Notă adăugată.',
    'remove_password_required' => 'Introduceți parola pentru a elimina acest proprietar.',
    'password_wrong' => 'Parola nu este corectă — introduceți parola cu care vă conectați.',
    'confirm_name_required' => 'Tastați numele proprietarului pentru confirmare.',
    'confirm_name_mismatch' => 'Numele tastat nu corespunde — proprietarul nu a fost eliminat.',
    'owner_removed' => 'Proprietarul :name a fost eliminat — unitățile nu mai au proprietar, toate deconturile și viramentele sunt păstrate pentru contabilitate.',
    'workspace_paused_until' => 'Organizație în pauză — datele proprietarilor sunt doar în citire până la :date.',
    'workspace_paused_for_now' => 'Organizație în pauză — datele proprietarilor sunt deocamdată doar în citire.',
];
