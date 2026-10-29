<?php

// I18N-01 — de translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Der Aufenthalt wird wieder auf „Bestätigt“ gesetzt, und das Zimmer bleibt für dieselben Nächte blockiert.',
    'history_kept' => 'Der Check-in selbst bleibt in der Buchungshistorie. Er hat stattgefunden, und der Eintrag hält das fest — das Rückgängigmachen wird daneben ergänzt.',
    'charges_kept' => '{1} :count Posten, der seit dem Check-in erfasst wurde (insgesamt :amount), bleibt auf dem Folio. Eine Anreise rückgängig zu machen, macht ausgegebenes Geld nicht ungeschehen — entfernen Sie den Posten auf dem Folio, falls er ebenfalls ein Fehler war.|[0,*] :count Posten, die seit dem Check-in erfasst wurden (insgesamt :amount), bleiben auf dem Folio. Eine Anreise rückgängig zu machen, macht ausgegebenes Geld nicht ungeschehen — entfernen Sie die Posten auf dem Folio, falls sie ebenfalls ein Fehler waren.',
    'online_check_in_kept' => 'Der Online-Check-in des Gastes bleibt abgeschlossen. Der Gast hat ihn tatsächlich ausgefüllt und wird nicht gebeten, das noch einmal zu tun.',
    'already_reversed' => 'Dieser Check-in wurde gerade eben bereits rückgängig gemacht — der Aufenthalt ist wieder bestätigt.',
    'refused_not_checked_in' => 'Nur bei einem eingecheckten Aufenthalt kann der Check-in rückgängig gemacht werden; dieser hat den Status „:status“.',
];
