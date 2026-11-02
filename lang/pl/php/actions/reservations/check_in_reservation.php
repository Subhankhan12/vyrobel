<?php

// I18N-01 — pl translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Nie można zameldować anulowanej rezerwacji: rezerwacja jest zamknięta, a pokój mógł już zostać ponownie sprzedany. Jeśli gość wróci, potrzebna będzie nowa rezerwacja.',
    'refused_no_show' => 'Nie można zameldować rezerwacji oznaczonej jako niepojawienie się: rezerwacja jest zamknięta, a jej noce wróciły do sprzedaży. Aby zakwaterować tego gościa, utwórz w recepcji nową rezerwację („Nowa rezerwacja”) – przed zarezerwowaniem ponownie sprawdzi ona dostępność. Niepojawienie się i ewentualna już naliczona opłata pozostają w profilu gościa.',
    'refused_checked_out' => 'Nie można zameldować wymeldowanej rezerwacji: wyjazd został już zapisany i obsłużony. Cofnięcie wymeldowania nie jest dostępne.',
    'refused_tentative' => 'Nie można zameldować rezerwacji wstępnej: pokój jest zablokowany, ale niepotwierdzony, więc nikt jeszcze nie obiecał temu gościowi pobytu. Najpierw kliknij w tej rezerwacji przycisk „Potwierdź rezerwację wstępną”, a potem zamelduj gościa. Noce są już zajęte przez tę rezerwację, więc potwierdzenie niczego dodatkowo nie zajmuje i nie może się nie udać z powodu dostępności.',
    'refused_other' => 'Nie można zameldować rezerwacji o statusie „:status”.',
];
