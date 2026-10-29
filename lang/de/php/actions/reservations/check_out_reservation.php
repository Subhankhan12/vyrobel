<?php

// I18N-01 — de translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Das ist kein bekannter Grund für eine Ausnahme beim Check-out.',
    'override_note_required' => 'Eine Ausnahme mit dem Grund „Sonstiges“ braucht eine Notiz, die beschreibt, was passiert ist.',
    'refused_confirmed' => 'Eine bestätigte Buchung kann nicht ausgecheckt werden: Ein Aufenthalt muss eingecheckt sein, bevor er ausgecheckt werden kann. Wenn der Gast übernachtet hat und früher abreist, checken Sie ihn zuerst ein und dann aus. Wenn er nie angekommen ist, markieren Sie die Buchung stattdessen als No-Show.',
    'refused_closed' => 'Eine Buchung im Status „:status“ kann nicht ausgecheckt werden: Die Buchung ist abgeschlossen und es fand kein Aufenthalt statt. Ein Gast, der doch noch erscheint, braucht eine neue Buchung.',
    'refused_other' => 'Eine Buchung im Status „:status“ kann nicht ausgecheckt werden.',
];
