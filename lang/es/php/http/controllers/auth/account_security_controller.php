<?php

// I18N-01 — es translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'Todavía no se puede activar la aprobación de inicio de sesión: Vyrobel todavía no envía correos electrónicos, así que el enlace de aprobación nunca le llegaría y un dispositivo nuevo no podría iniciar sesión.',
    'login_approval_on' => 'La aprobación de inicio de sesión está activada: un dispositivo nuevo espera hasta que usted lo apruebe.',
    'login_approval_off' => 'La aprobación de inicio de sesión está desactivada.',
    'approval_no_longer_waiting' => 'Ese inicio de sesión ya no está en espera: ya se decidió o ha caducado.',
    'approval_approved' => 'Aprobado: ese dispositivo ya puede terminar de iniciar sesión.',
    'approval_denied' => 'Denegado: ese inicio de sesión no puede continuar. Si no fue usted, cambie su contraseña.',
    'login_alerts_on' => 'Las alertas de inicio de sesión están activadas: un inicio de sesión desde un dispositivo nuevo aparece en sus notificaciones.',
    'login_alerts_off' => 'Las alertas de inicio de sesión están desactivadas.',
    'session_timeout_off' => 'El cierre de sesión por inactividad está desactivado: la sesión sigue abierta durante :duration sin actividad, como antes.',
    'session_timeout_on' => 'Su sesión se cerrará tras :duration sin actividad.',
];
