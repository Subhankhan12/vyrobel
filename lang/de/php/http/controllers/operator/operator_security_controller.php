<?php

// I18N-01 — de translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Geben Sie eine gültige IP-Adresse oder einen CIDR-Block ein (z. B. 203.0.113.4 oder 203.0.113.0/24).',
    'ip_already_listed' => ':ip steht bereits auf der Zulassungsliste.',
    'allowlist_enabled_auto_added' => 'IP-Zulassungsliste aktiviert. Wir haben auch Ihre aktuelle Adresse (:ip) hinzugefügt, damit Sie sich nicht selbst aussperren.',
    'ip_added' => ':ip zur Zulassungsliste hinzugefügt.',
    'remove_would_lock_out' => 'Wenn Sie :ip entfernen, sperren Sie sich selbst aus — es ist die einzige Regel, mit der Ihre Adresse (:current) das Backoffice erreicht. Fügen Sie zuerst eine Regel für Ihre Adresse hinzu oder entfernen Sie die anderen.',
    'ip_removed_allowlist_off' => ':ip entfernt — die Zulassungsliste ist deaktiviert: Jeder mit einer gültigen Anmeldung kann das Backoffice erreichen.',
    'ip_removed' => ':ip von der Zulassungsliste entfernt.',
    'session_already_ended' => 'Diese Sitzung wurde bereits beendet.',
    'signed_out_of_device' => 'Von :device abgemeldet.',
    'trusted_device_revoked' => 'Vertrauen für Gerät entzogen.',
    'csv_title' => 'Vyrobel-Kontoexport',
    'csv_name' => 'Name',
    'csv_email' => 'E-Mail-Adresse',
    'csv_locale' => 'Sprache',
    'csv_roles' => 'Rollen',
    'csv_activity' => 'Aktivität',
    'csv_when' => 'Zeitpunkt',
    'csv_action' => 'Aktion',
    'range_this_year' => 'Dieses Jahr',
    'range_custom' => 'Eigener Zeitraum',
    'range_all_time' => 'Seit Beginn',
];
