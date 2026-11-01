<?php

// I18N-01 — es translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'La estancia vuelve a tener el check-in realizado.',
    'pending_tasks_removed' => '{1} Se quitará :count tarea de limpieza de salida: todavía no ha empezado.|[0,*] Se quitarán :count tareas de limpieza de salida: todavía no han empezado.',
    'started_tasks_kept' => '{1} :count tarea de limpieza ya se ha empezado y no se tocará: cancelar un trabajo que alguien ya ha empezado es una decisión de Limpieza, no algo que se deshace.|[0,*] :count tareas de limpieza ya se han empezado y no se tocarán: cancelar un trabajo que alguien ya ha empezado es una decisión de Limpieza, no algo que se deshace.',
    'unit_stays_dirty' => 'La unidad sigue marcada como sucia. El huésped vuelve a estar en la habitación, así que “limpia” podría no ser cierto.',
    'notifications_stay_delivered' => 'Las notificaciones ya entregadas al personal y al huésped no se retiran.',
    'review_unknown' => 'No consta si se envió una solicitud de reseña para esta estancia, porque el check-out se hizo antes de que el producto empezara a registrarlo. Puede que se haya enviado o no.',
    'review_sent_on' => 'Se envió al huésped una solicitud de reseña por correo electrónico el :date. No se puede anular.',
    'review_sent' => 'Se envió al huésped una solicitud de reseña por correo electrónico. No se puede anular.',
    'review_suppressed_by_operator' => 'No se envió ninguna solicitud de reseña: este alojamiento tiene desactivado el correo de solicitud de reseña.',
    'review_suppressed_by_guest' => 'No se envió ninguna solicitud de reseña: el huésped pidió no recibir correos de reseñas.',
    'review_not_sent' => 'No se envió ninguna solicitud de reseña: no había dirección de contacto o el envío falló.',
    'refused_not_checked_out' => 'Solo se puede deshacer el check-out de una estancia con el check-out realizado; esta tiene el estado “:status”.',
];
