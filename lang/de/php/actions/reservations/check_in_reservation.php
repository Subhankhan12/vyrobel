<?php

// I18N-01 — de translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Eine stornierte Buchung kann nicht eingecheckt werden: Die Buchung ist abgeschlossen und das Zimmer wurde möglicherweise bereits neu verkauft. Ein Gast, der zurückkommt, braucht eine neue Buchung.',
    'refused_no_show' => 'Eine als No-Show markierte Buchung kann nicht eingecheckt werden: Die Buchung ist abgeschlossen und ihre Nächte wurden wieder zum Verkauf freigegeben. Um diesen Gast in einem Zimmer unterzubringen, legen Sie an der Rezeption einen „Walk-in“ an — dabei wird die Verfügbarkeit vor dem Buchen erneut geprüft. Der No-Show und eine bereits dafür belastete Gebühr bleiben im Gastprofil erhalten.',
    'refused_checked_out' => 'Eine ausgecheckte Buchung kann nicht eingecheckt werden: Die Abreise wurde bereits erfasst und verarbeitet. Falls der Check-out ein Versehen war, verwenden Sie zuerst in der Buchung „Check-out rückgängig machen“.',
    'refused_tentative' => 'Eine Optionsbuchung kann nicht eingecheckt werden: Das Zimmer ist vorgemerkt, aber nicht bestätigt, daher wurde diesem Gast der Aufenthalt noch nicht zugesagt. Klicken Sie zuerst bei dieser Buchung auf „Option bestätigen“ und checken Sie den Gast dann ein. Die Nächte sind durch diese Buchung bereits blockiert, daher erfordert die Bestätigung nichts weiter und kann nicht an der Verfügbarkeit scheitern.',
    'refused_other' => 'Eine Buchung im Status „:status“ kann nicht eingecheckt werden.',
];
