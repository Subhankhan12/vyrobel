<?php

// I18N-01 — pl translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Rezerwacja wraca do statusu „Potwierdzona”, a pokój pozostaje zarezerwowany na te same noce.',
    'history_kept' => 'Samo zameldowanie pozostaje w historii rezerwacji. Miało miejsce i zapis to odnotowuje – cofnięcie jest dodawane obok niego.',
    'charges_kept' => '{1}:count obciążenie zaksięgowane od zameldowania, na łączną kwotę :amount, pozostaje w folio. Cofnięcie przyjazdu nie cofa wydanych pieniędzy – jeśli to obciążenie też było pomyłką, usuń je w folio.|:count obciążenia zaksięgowane od zameldowania, na łączną kwotę :amount, pozostają w folio. Cofnięcie przyjazdu nie cofa wydanych pieniędzy – jeśli te obciążenia też były pomyłką, usuń je w folio.|:count obciążeń zaksięgowanych od zameldowania, na łączną kwotę :amount, pozostaje w folio. Cofnięcie przyjazdu nie cofa wydanych pieniędzy – jeśli te obciążenia też były pomyłką, usuń je w folio.',
    'online_check_in_kept' => 'Zameldowanie online wykonane przez gościa pozostaje zakończone. Gość naprawdę je wypełnił, a ta operacja nie prosi go o ponowne wypełnienie.',
    'already_reversed' => 'To zameldowanie zostało już przed chwilą cofnięte – rezerwacja ma znów status „Potwierdzona”.',
    'refused_not_checked_in' => 'Cofnąć zameldowanie można tylko w rezerwacji o statusie „Zameldowana”; ta rezerwacja ma status „:status”.',
];
