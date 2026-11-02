<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'Mechanizm wypłat jest wyłączony (PAYOUT_ENGINE_ENABLED). Włącz go przed uruchomieniem wypłaty.',
    'nothing_to_run' => 'Nie ma czego uruchomić – żaden właściciel nie ma rozliczenia do wypłaty ani zatrzymanej wypłaty:paused_note.',
    'run_complete' => 'Wypłata zbiorcza zakończona (tryb „:mode”) – utworzono: :created · wypłacono: :paid · zatrzymano: :held:paused_note.',
    'create_failed' => 'Nie udało się utworzyć wypłaty – :reason',
    'already_has_payout' => 'To rozliczenie ma już wypłatę – niczego nie utworzono.',
    'one_payout_created' => 'Utworzono 1 wypłatę.',
    'pay_failed' => 'Nie udało się wypłacić – :reason',
    'one_payout_paid' => 'Oznaczono 1 wypłatę jako wypłaconą.',
    'payout_failed_reason' => 'Wypłata nie powiodła się – :reason',
    'payout_failed_no_reason' => 'Wypłata nie powiodła się – kanał wypłaty nie podał przyczyny.',
    'create_all_failed' => 'Nie udało się utworzyć wypłat – :reason',
    'no_payout_created_paused' => 'Nie utworzono żadnej wypłaty:paused_note – wznów wypłaty na stronie właściciela.',
    'nothing_ready' => 'Nic nie jest gotowe do wypłaty – żadne zamknięte rozliczenie nie ma dodatniej kwoty do wypłaty:paused_note.',
    'no_new_payouts' => 'Brak nowych wypłat – każde gotowe rozliczenie ma już wypłatę:paused_note.',
    'payouts_created' => '{1}Utworzono :count wypłatę:paused_note.|Utworzono :count wypłaty:paused_note.|Utworzono :count wypłat:paused_note.',
    'some_not_created' => ':message Niektórych wypłat nie można było utworzyć – :reason',
    'nothing_paid_paused' => 'Niczego nie wypłacono:paused_note – wznów wypłaty na stronie właściciela.',
    'no_pending_payouts' => 'Brak oczekujących wypłat do realizacji.',
    'paid_only' => '{1}Oznaczono :count wypłatę jako wypłaconą:paused_note.|Oznaczono :count wypłaty jako wypłacone:paused_note.|Oznaczono :count wypłat jako wypłacone:paused_note.',
    'failed_only' => '{1}:count wypłata nie powiodła się w kanale wypłaty:paused_note.|:count wypłaty nie powiodły się w kanale wypłaty:paused_note.|:count wypłat nie powiodło się w kanale wypłaty:paused_note.',
    'paid_and_failed' => '{1}Oznaczono :count wypłatę jako wypłaconą · nieudane w kanale wypłaty: :failed:paused_note.|Oznaczono :count wypłaty jako wypłacone · nieudane w kanale wypłaty: :failed:paused_note.|Oznaczono :count wypłat jako wypłacone · nieudane w kanale wypłaty: :failed:paused_note.',
    'some_not_attempted' => ':message Niektórych wypłat nie można było zlecić – :reason',
    'nothing_payable_negative' => 'Brak kwoty do wypłaty – dochód z tego rozliczenia jest ujemny, więc saldo zostaje przeniesione i pomniejszy następne rozliczenie właściciela.',
    'nothing_payable_zero' => 'Brak kwoty do wypłaty – dochód z tego rozliczenia wynosi zero, więc nic nie jest należne.',
    'workspace_paused_until' => 'Organizacja wstrzymana – wypłaty są tylko do odczytu do :date.',
    'workspace_paused_for_now' => 'Organizacja wstrzymana – wypłaty są na razie tylko do odczytu.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1}pominięto :count właściciela z wstrzymanymi wypłatami|pominięto :count właścicieli z wstrzymanymi wypłatami|pominięto :count właścicieli z wstrzymanymi wypłatami',
];
