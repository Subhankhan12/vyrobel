<?php

// I18N-01 — es translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => 'Solo se puede volver a emitir una invitación pendiente.',
    'last_owner_role' => 'Este es el último “Administrador principal” activo. Primero asigne ese rol a otra persona.',
    'role_changed' => ':name tiene ahora el rol “:role”.',
    'cannot_revoke_self' => 'No puede revocar su propio acceso. Pídaselo a otro “Administrador principal”.',
    'last_owner_revoke' => 'Este es el último “Administrador principal” activo y no se puede revocar su acceso.',
    'access_revoked' => '{1} Se revocó el acceso de :name; se cerró :count sesión.|[0,*] Se revocó el acceso de :name; se cerraron :count sesiones.',
    'only_revoked_restored' => 'Solo se puede restablecer el acceso de un administrador con el acceso revocado.',
    'access_restored' => 'Se restableció el acceso de :name.',
    'ended_own_other_sessions' => '{1} Se cerró :count de sus otras sesiones: esta sigue abierta.|[0,*] Se cerraron :count de sus otras sesiones: esta sigue abierta.',
    'ended_sessions_for' => '{1} Se cerró :count sesión de :name.|[0,*] Se cerraron :count sesiones de :name.',
];
