<?php

// I18N-01 — ru translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Введите корректный IP-адрес или блок CIDR (например, 203.0.113.4 или 203.0.113.0/24).',
    'ip_already_listed' => 'Адрес :ip уже есть в списке разрешённых.',
    'allowlist_enabled_auto_added' => 'Список разрешённых IP включён. Мы также добавили ваш текущий адрес (:ip), чтобы вы не потеряли доступ.',
    'ip_added' => 'Адрес :ip добавлен в список разрешённых.',
    'remove_would_lock_out' => 'Если убрать :ip, вы потеряете доступ — это единственное правило, которое пропускает ваш адрес (:current) в бэк-офис. Сначала добавьте правило для своего адреса или уберите остальные.',
    'ip_removed_allowlist_off' => 'Адрес :ip убран — список разрешённых IP выключен: в бэк-офис может попасть любой, у кого есть действующие данные для входа.',
    'ip_removed' => 'Адрес :ip убран из списка разрешённых.',
    'session_already_ended' => 'Этот сеанс уже завершён.',
    'signed_out_of_device' => 'Выполнен выход на устройстве :device.',
    'trusted_device_revoked' => 'Доверенное устройство отозвано.',
    'csv_title' => 'Экспорт аккаунта Vyrobel',
    'csv_name' => 'Имя',
    'csv_email' => 'Эл. почта',
    'csv_locale' => 'Язык',
    'csv_roles' => 'Роли',
    'csv_activity' => 'Активность',
    'csv_when' => 'Когда',
    'csv_action' => 'Действие',
    'range_this_year' => 'Этот год',
    'range_custom' => 'Свой период',
    'range_all_time' => 'За всё время',
];
