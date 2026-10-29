<?php

// I18N-01 — de translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Der Aufenthalt wird wieder auf „Eingecheckt“ gesetzt.',
    'pending_tasks_removed' => '{1} :count Reinigungsaufgabe zur Abreise wird entfernt — noch nicht begonnen.|[0,*] :count Reinigungsaufgaben zur Abreise werden entfernt — noch nicht begonnen.',
    'started_tasks_kept' => '{1} :count Reinigungsaufgabe wurde bereits begonnen und bleibt unverändert — begonnene Arbeit abzubrechen ist eine Entscheidung des Housekeepings, kein Rückgängigmachen.|[0,*] :count Reinigungsaufgaben wurden bereits begonnen und bleiben unverändert — begonnene Arbeit abzubrechen ist eine Entscheidung des Housekeepings, kein Rückgängigmachen.',
    'unit_stays_dirty' => 'Die Einheit bleibt als schmutzig markiert. Der Gast ist wieder im Zimmer, daher stimmt „sauber“ möglicherweise nicht.',
    'notifications_stay_delivered' => 'Bereits zugestellte Benachrichtigungen an Mitarbeiter und Gast bleiben zugestellt.',
    'review_unknown' => 'Es ist nicht erfasst, ob für diesen Aufenthalt eine Bewertungsanfrage gesendet wurde, da der Check-out stattfand, bevor dieses Produkt solche Anfragen erfasste. Sie wurde möglicherweise gesendet, möglicherweise nicht.',
    'review_sent_on' => 'Am :date wurde dem Gast eine Bewertungsanfrage per E-Mail gesendet. Sie kann nicht zurückgerufen werden.',
    'review_sent' => 'Dem Gast wurde eine Bewertungsanfrage per E-Mail gesendet. Sie kann nicht zurückgerufen werden.',
    'review_suppressed_by_operator' => 'Es wurde keine Bewertungsanfrage gesendet — bei dieser Unterkunft ist die E-Mail mit der Bewertungsanfrage ausgeschaltet.',
    'review_suppressed_by_guest' => 'Es wurde keine Bewertungsanfrage gesendet — der Gast möchte keine Bewertungs-E-Mails erhalten.',
    'review_not_sent' => 'Es wurde keine Bewertungsanfrage gesendet — es gab keine Kontaktadresse, oder der Versand ist fehlgeschlagen.',
    'refused_not_checked_out' => 'Nur bei einem ausgecheckten Aufenthalt kann der Check-out rückgängig gemacht werden; dieser hat den Status „:status“.',
];
