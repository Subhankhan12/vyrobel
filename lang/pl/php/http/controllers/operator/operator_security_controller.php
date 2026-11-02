<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Wpisz prawidłowy adres IP lub blok CIDR (np. 203.0.113.4 lub 203.0.113.0/24).',
    'ip_already_listed' => 'Adres :ip jest już na liście dozwolonych adresów.',
    'allowlist_enabled_auto_added' => 'Włączono listę dozwolonych adresów IP. Dodaliśmy też Państwa bieżący adres (:ip), aby nie zablokować Państwu dostępu.',
    'ip_added' => 'Dodano adres :ip do listy dozwolonych adresów.',
    'remove_would_lock_out' => 'Usunięcie adresu :ip zablokowałoby Państwu dostęp – to jedyna reguła, która dopuszcza Państwa adres (:current) do panelu personelu. Najpierw dodaj regułę dla tego adresu albo usuń pozostałe.',
    'ip_removed_allowlist_off' => 'Usunięto adres :ip – lista dozwolonych adresów jest wyłączona: każdy z prawidłowymi danymi logowania ma dostęp do panelu personelu.',
    'ip_removed' => 'Usunięto adres :ip z listy dozwolonych adresów.',
    'session_already_ended' => 'Ta sesja już się zakończyła.',
    'signed_out_of_device' => 'Wylogowano z urządzenia: :device.',
    'trusted_device_revoked' => 'Cofnięto zaufanie do urządzenia.',
    'csv_title' => 'Eksport konta Vyrobel',
    'csv_name' => 'Imię i nazwisko',
    'csv_email' => 'E-mail',
    'csv_locale' => 'Język',
    'csv_roles' => 'Role',
    'csv_activity' => 'Aktywność',
    'csv_when' => 'Kiedy',
    'csv_action' => 'Działanie',
    'range_this_year' => 'Ten rok',
    'range_custom' => 'Własny zakres',
    'range_all_time' => 'Cały okres',
];
