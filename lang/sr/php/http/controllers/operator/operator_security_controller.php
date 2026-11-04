<?php

// I18N-01 — sr translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Унесите исправну IP адресу или CIDR опсег (нпр. 203.0.113.4 или 203.0.113.0/24).',
    'ip_already_listed' => 'Већ је на листи дозвољених адреса: :ip.',
    'allowlist_enabled_auto_added' => 'Листа дозвољених IP адреса је укључена. Додата је и ваша тренутна адреса (:ip) да не бисте остали без приступа.',
    'ip_added' => 'Додато на листу дозвољених адреса: :ip.',
    'remove_would_lock_out' => 'Уклањање правила за :ip онемогућило би вам приступ – то је једино правило које вашој адреси (:current) омогућава приступ панелу за особље. Прво додајте правило за своју адресу или уклоните остала.',
    'ip_removed_allowlist_off' => 'Уклоњено: :ip – листа дозвољених адреса је искључена: свако ко се успешно пријави може да приступи панелу за особље.',
    'ip_removed' => 'Уклоњено са листе дозвољених адреса: :ip.',
    'session_already_ended' => 'Та сесија је већ завршена.',
    'signed_out_of_device' => 'Одјављено са уређаја: :device.',
    'trusted_device_revoked' => 'Поуздани уређај је укинут.',
    'csv_title' => 'Извоз налога Vyrobel',
    'csv_name' => 'Име',
    'csv_email' => 'Е-пошта',
    'csv_locale' => 'Језик',
    'csv_roles' => 'Улоге',
    'csv_activity' => 'Активност',
    'csv_when' => 'Када',
    'csv_action' => 'Радња',
    'range_this_year' => 'Ова година',
    'range_custom' => 'Прилагођени период',
    'range_all_time' => 'Цео период',
];
