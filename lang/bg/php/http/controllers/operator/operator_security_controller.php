<?php

// I18N-01 — bg translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Въведете валиден IP адрес или CIDR блок (напр. 203.0.113.4 или 203.0.113.0/24).',
    'ip_already_listed' => 'IP адрес :ip вече е в списъка с разрешени.',
    'allowlist_enabled_auto_added' => 'Списъкът с разрешени IP адреси е включен. Добавихме и текущия ви адрес (:ip), за да не се заключите.',
    'ip_added' => 'IP адрес :ip е добавен в списъка с разрешени.',
    'remove_would_lock_out' => 'Премахването на IP адрес :ip би ви заключило – това е единственото правило, което допуска адреса ви (:current) до панела за персонала. Първо добавете правило за адреса си или премахнете останалите.',
    'ip_removed_allowlist_off' => 'IP адрес :ip е премахнат – списъкът е изключен: всеки с валидни данни за вход има достъп до панела за персонала.',
    'ip_removed' => 'IP адрес :ip е премахнат от списъка с разрешени.',
    'session_already_ended' => 'Тази сесия вече е приключила.',
    'signed_out_of_device' => 'Излязохте от устройство :device.',
    'trusted_device_revoked' => 'Доверието към устройството е отнето.',
    'csv_title' => 'Експортиране на акаунта във Vyrobel',
    'csv_name' => 'Име',
    'csv_email' => 'Имейл',
    'csv_locale' => 'Език',
    'csv_roles' => 'Роли',
    'csv_activity' => 'Активност',
    'csv_when' => 'Кога',
    'csv_action' => 'Действие',
    'range_this_year' => 'Тази година',
    'range_custom' => 'Персонализиран период',
    'range_all_time' => 'За целия период',
];
