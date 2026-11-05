<?php

// I18N-01 — es translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Introduzca la comisión, en porcentaje.',
    'commission_range' => 'La comisión debe ser un número entre 0 y 100.',
    'commission_unchanged' => 'No cambió nada: :rate ya es la comisión de :name desde el :date.',
    'commission_from_today' => 'La comisión de :name es :rate a partir de hoy: no tenía ningún contrato en vigor.',
    'commission_waiting_cancelled' => ':name mantiene :rate: el cambio previsto para el :date no se aplicará.',
    'commission_from_date' => 'La comisión de :name es :rate a partir del :date, para las reservas que se calculen desde ahora. Las reservas ya calculadas conservan la comisión que tenían.',
    'payouts_paused' => 'Liquidaciones en pausa para :name: no se le hará ninguna liquidación hasta que usted las reanude.',
    'payouts_resumed' => 'Se reanudaron las liquidaciones de :name.',
    'period_start_required' => 'Seleccione el primer día del periodo.',
    'period_end_required' => 'Seleccione el último día del periodo.',
    'period_end_after_start' => 'El periodo debe terminar el mismo día en que empieza o después.',
    'period_not_closed' => 'No se pudo cerrar ese periodo: :reason Elija un periodo que empiece después del último periodo sellado.',
    'period_sealed' => 'No se puede volver a generar un extracto finalizado o bloqueado.',
    'period_not_closed_other' => 'No se pudo generar el extracto de ese periodo.',
    'period_closed' => 'Periodo cerrado: se finalizó el extracto n.º :number con :amount a liquidar. Los ingresos que todavía no se han cobrado a los huéspedes quedan pendientes y aparecerán en un extracto posterior.',
    'note_required' => 'Primero escriba la nota.',
    'note_max' => 'Una nota puede tener hasta 2000 caracteres.',
    'note_added' => 'Se agregó la nota.',
    'remove_password_required' => 'Introduzca su contraseña para quitar a este propietario.',
    'password_wrong' => 'Esa contraseña no es correcta. Introduzca la que usa para iniciar sesión.',
    'confirm_name_required' => 'Escriba el nombre del propietario para confirmar.',
    'confirm_name_mismatch' => 'El nombre que escribió no coincide: no se quitó al propietario.',
    'owner_removed' => 'Se quitó a :name: se desasignaron sus unidades y se conservan todos los extractos y liquidaciones para la contabilidad.',
    'workspace_paused_until' => 'Organización en pausa: los cambios de propietarios están en modo de solo lectura hasta el :date.',
    'workspace_paused_for_now' => 'Organización en pausa: por ahora, los cambios de propietarios están en modo de solo lectura.',
];
