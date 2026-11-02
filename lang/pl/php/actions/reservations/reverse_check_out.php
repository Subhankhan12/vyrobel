<?php

// I18N-01 — pl translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Rezerwacja wraca do statusu „Zameldowana”.',
    'pending_tasks_removed' => '{1}:count zadanie sprzątania po wyjeździe zostanie usunięte – nie zostało jeszcze rozpoczęte.|:count zadania sprzątania po wyjeździe zostaną usunięte – nie zostały jeszcze rozpoczęte.|:count zadań sprzątania po wyjeździe zostanie usuniętych – nie zostały jeszcze rozpoczęte.',
    'started_tasks_kept' => '{1}:count zadanie sprzątania zostało już rozpoczęte i pozostanie bez zmian – anulowanie pracy, którą ktoś zaczął, to decyzja działu sprzątania, a nie cofnięcie operacji.|:count zadania sprzątania zostały już rozpoczęte i pozostaną bez zmian – anulowanie pracy, którą ktoś zaczął, to decyzja działu sprzątania, a nie cofnięcie operacji.|:count zadań sprzątania zostało już rozpoczętych i pozostanie bez zmian – anulowanie pracy, którą ktoś zaczął, to decyzja działu sprzątania, a nie cofnięcie operacji.',
    'unit_stays_dirty' => 'Jednostka pozostaje oznaczona jako brudna. Gość jest z powrotem w pokoju, więc status „Czysta” może nie odpowiadać prawdzie.',
    'notifications_stay_delivered' => 'Powiadomienia już dostarczone do personelu i gościa pozostają dostarczone.',
    'review_unknown' => 'Brak zapisu, czy dla tego pobytu wysłano prośbę o opinię, ponieważ wymeldowanie nastąpiło, zanim system zaczął to rejestrować. Prośba mogła, ale nie musiała zostać wysłana.',
    'review_sent_on' => 'Prośba o opinię została wysłana do gościa e-mailem w dniu :date. Nie można jej wycofać.',
    'review_sent' => 'Prośba o opinię została wysłana do gościa e-mailem. Nie można jej wycofać.',
    'review_suppressed_by_operator' => 'Nie wysłano prośby o opinię – w tym obiekcie e-mail z prośbą o opinię jest wyłączony.',
    'review_suppressed_by_guest' => 'Nie wysłano prośby o opinię – gość poprosił, aby nie wysyłać mu e-maili z prośbą o opinię.',
    'review_not_sent' => 'Nie wysłano prośby o opinię – brak adresu kontaktowego lub wysyłka się nie powiodła.',
    'refused_not_checked_out' => 'Cofnąć wymeldowanie można tylko w rezerwacji o statusie „Wymeldowana”; ta rezerwacja ma status „:status”.',
];
