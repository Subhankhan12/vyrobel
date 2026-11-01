<?php

// I18N-01 — es translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Interruptores de emergencia',
    'subtitle' => 'Detenga los nuevos registros, o el inicio de sesión con un proveedor, sin necesidad de un despliegue. Cada cambio queda auditado con un motivo.',
    'explainer' => 'Todos los interruptores están <strong>activados</strong> salvo que alguien los desactive aquí, incluso justo después de un despliegue. Ninguno puede afectar a esta consola. Desactivar el <strong>inicio de sesión</strong> con un proveedor bloquea a cualquiera cuya única forma de entrar sea ese proveedor, por lo que esos interruptores muestran el número de cuentas y piden confirmación dos veces.',
    'state_on' => 'Activado',
    'state_off' => 'Desactivado',
    'last_changed_by' => 'Último cambio: :time UTC, por :name.',
    'last_changed' => 'Último cambio: :time UTC.',
    'never_changed' => 'Nunca modificado: activado por defecto.',
    'no_account_relies' => 'Ahora mismo ninguna cuenta depende solo de este proveedor: todas tienen también una contraseña u otra forma de entrar.',
    'would_lock_out' => '{1} Desactivarlo bloquearía <strong>:count</strong> cuenta: no tiene contraseña y este proveedor es su única forma de entrar.|[0,*] Desactivarlo bloquearía <strong>:count</strong> cuentas: no tienen contraseña y este proveedor es su única forma de entrar.',
    'reason' => 'Motivo',
    'why_turn_off' => '¿Por qué lo desactiva?',
    'why_turn_on' => '¿Por qué lo activa?',
    'confirm_lockout_zero' => 'Entiendo que cualquiera que inicie sesión solo de esta forma quedará bloqueado desde ahora hasta que se vuelva a activar.',
    'confirm_lockout' => '{1} Entiendo que esto bloquea :count cuenta hasta que se vuelva a activar.|[0,*] Entiendo que esto bloquea :count cuentas hasta que se vuelva a activar.',
    'turn_off' => 'Desactivar',
    'turn_on' => 'Activar',
];
