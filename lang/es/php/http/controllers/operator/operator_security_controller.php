<?php

// I18N-01 — es translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Introduzca una dirección IP o un bloque CIDR válidos (p. ej., 203.0.113.4 o 203.0.113.0/24).',
    'ip_already_listed' => ':ip ya está en la lista de IP permitidas.',
    'allowlist_enabled_auto_added' => 'Se activó la lista de IP permitidas. También agregamos su dirección actual (:ip) para que no se quede sin acceso.',
    'ip_added' => 'Se agregó :ip a la lista de IP permitidas.',
    'remove_would_lock_out' => 'Si quita :ip, se quedará sin acceso: es la única regla que permite que su dirección (:current) acceda al back office. Primero agregue una regla para su dirección o quite las demás.',
    'ip_removed_allowlist_off' => 'Se quitó :ip y la lista de IP permitidas está desactivada: cualquier persona con credenciales válidas puede acceder al back office.',
    'ip_removed' => 'Se quitó :ip de la lista de IP permitidas.',
    'session_already_ended' => 'Esa sesión ya finalizó.',
    'signed_out_of_device' => 'Se cerró la sesión en :device.',
    'trusted_device_revoked' => 'Se revocó el dispositivo de confianza.',
    'csv_title' => 'Exportación de la cuenta de Vyrobel',
    'csv_name' => 'Nombre',
    'csv_email' => 'Correo electrónico',
    'csv_locale' => 'Idioma',
    'csv_roles' => 'Roles',
    'csv_activity' => 'Actividad',
    'csv_when' => 'Fecha',
    'csv_action' => 'Acción',
    'range_this_year' => 'Este año',
    'range_custom' => 'Periodo personalizado',
    'range_all_time' => 'Todo el periodo',
];
