<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Introduceți o adresă IP validă sau un bloc CIDR (de ex. 203.0.113.4 sau 203.0.113.0/24).',
    'ip_already_listed' => 'Adresa :ip este deja în lista de IP-uri permise.',
    'allowlist_enabled_auto_added' => 'Lista de IP-uri permise a fost activată. Am adăugat și adresa dumneavoastră actuală (:ip), ca să nu vă blocați singur accesul.',
    'ip_added' => 'Adresa :ip a fost adăugată în lista de IP-uri permise.',
    'remove_would_lock_out' => 'Eliminarea adresei :ip v-ar bloca accesul — este singura regulă care permite adresei dumneavoastră (:current) să acceseze back office-ul. Adăugați mai întâi o regulă pentru adresa dumneavoastră sau eliminați-le pe celelalte.',
    'ip_removed_allowlist_off' => 'Adresa :ip a fost eliminată — lista de IP-uri permise este dezactivată: oricine are date de conectare valide poate accesa back office-ul.',
    'ip_removed' => 'Adresa :ip a fost eliminată din lista de IP-uri permise.',
    'session_already_ended' => 'Această sesiune s-a încheiat deja.',
    'signed_out_of_device' => 'Sesiunea de pe :device a fost închisă.',
    'trusted_device_revoked' => 'Dispozitivul de încredere a fost revocat.',
    'csv_title' => 'Export cont Vyrobel',
    'csv_name' => 'Nume',
    'csv_email' => 'E-mail',
    'csv_locale' => 'Limbă',
    'csv_roles' => 'Roluri',
    'csv_activity' => 'Activitate',
    'csv_when' => 'Data și ora',
    'csv_action' => 'Acțiune',
    'range_this_year' => 'Anul acesta',
    'range_custom' => 'Interval personalizat',
    'range_all_time' => 'Toată perioada',
];
