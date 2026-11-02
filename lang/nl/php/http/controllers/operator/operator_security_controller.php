<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Voer een geldig IP-adres of CIDR-blok in (bijv. 203.0.113.4 of 203.0.113.0/24).',
    'ip_already_listed' => ':ip staat al op de toegangslijst.',
    'allowlist_enabled_auto_added' => 'IP-toegangslijst ingeschakeld. We hebben ook uw huidige adres (:ip) toegevoegd, zodat u uzelf niet buitensluit.',
    'ip_added' => ':ip toegevoegd aan de toegangslijst.',
    'remove_would_lock_out' => 'Als u :ip verwijdert, sluit u uzelf buiten — het is de enige regel die uw adres (:current) toegang geeft tot de backoffice. Voeg eerst een regel voor uw adres toe, of verwijder de andere regels.',
    'ip_removed_allowlist_off' => ':ip verwijderd — de toegangslijst staat uit: iedereen met geldige inloggegevens kan de backoffice bereiken.',
    'ip_removed' => ':ip verwijderd van de toegangslijst.',
    'session_already_ended' => 'Die sessie is al beëindigd.',
    'signed_out_of_device' => 'Uitgelogd op :device.',
    'trusted_device_revoked' => 'Vertrouwd apparaat ingetrokken.',
    'csv_title' => 'Vyrobel-accountexport',
    'csv_name' => 'Naam',
    'csv_email' => 'E-mailadres',
    'csv_locale' => 'Taal',
    'csv_roles' => 'Rollen',
    'csv_activity' => 'Activiteit',
    'csv_when' => 'Wanneer',
    'csv_action' => 'Actie',
    'range_this_year' => 'Dit jaar',
    'range_custom' => 'Aangepaste periode',
    'range_all_time' => 'Alles',
];
