<?php

// I18N-01 — de translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Diese Verbindung existiert nicht mehr.',
    'unlink_refused_only_way_in' => 'Abgelehnt — dies ist der einzige Weg, auf dem sich diese Person anmelden kann (kein Passwort, kein anderer Anbieter). Das Trennen würde sie dauerhaft aussperren.',
    'unlinked' => ':provider getrennt — zurückgelesen: Die Verbindung ist entfernt.',
    'unlink_not_landed' => 'Das Trennen wurde nicht übernommen — die Verbindung besteht noch.',
    'already_verified' => 'Bereits bestätigt.',
    'marked_verified' => 'Als bestätigt markiert — aus der Datenbank zurückgelesen: :stored UTC.',
    'verify_not_landed' => 'Der Schreibvorgang wurde nicht übernommen — email_verified_at ist noch leer.',
    'already_verified_nothing_to_resend' => 'Bereits bestätigt — nichts erneut zu senden.',
    'verification_discarded' => 'NICHT ZUGESTELLT — der E-Mail-Versand ist „:mailer“, daher wurde die Bestätigungs-E-Mail verworfen. Niemand hat etwas erhalten. Bestätigen Sie die Identität der Person auf anderem Weg und verwenden Sie dann „E-Mail als bestätigt markieren“.',
    'verification_handed' => 'Bestätigungs-E-Mail an den Mailversand übergeben (:mailer).',
    'no_reset_link' => 'Es wurde kein Reset-Link erstellt: :status',
    'reset_discarded' => 'NICHT ZUGESTELLT — ein Reset-Link wurde erstellt, aber der E-Mail-Versand ist „:mailer“, daher wurde die E-Mail verworfen. Niemand hat etwas erhalten.',
    'reset_handed' => 'E-Mail zum Passwort-Reset an den Mailversand übergeben (:mailer).',
    'verification_not_sent_undeliverable' => 'NICHT GESENDET — diese Adresse kann nie E-Mails empfangen (:reason), daher wurde die Bestätigungs-E-Mail nicht an den Mailversand übergeben. Niemand hat etwas erhalten.',
    'reset_not_sent_undeliverable' => 'NICHT GESENDET — diese Adresse kann nie E-Mails empfangen (:reason), daher wurde der Reset-Link nicht an den Mailversand übergeben. Niemand hat etwas erhalten.',
];
