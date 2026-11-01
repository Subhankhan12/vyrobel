<?php

// I18N-01 — es translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Esa conexión ya no existe.',
    'unlink_refused_only_way_in' => 'Rechazado: esta es la única forma en que esta persona puede iniciar sesión (sin contraseña ni otro proveedor). Si se desvincula, se quedaría sin acceso para siempre.',
    'unlinked' => 'Se desvinculó :provider; se volvió a leer: la conexión ya no existe.',
    'unlink_not_landed' => 'La desvinculación no se aplicó: la conexión sigue existiendo.',
    'already_verified' => 'Ya está verificado.',
    'marked_verified' => 'Marcado como verificado; se volvió a leer de la base de datos: :stored UTC.',
    'verify_not_landed' => 'El cambio no se aplicó: email_verified_at sigue vacío.',
    'already_verified_nothing_to_resend' => 'Ya está verificado: no hay nada que reenviar.',
    'verification_discarded' => 'NO ENTREGADO: el correo está en “:mailer”, así que se descartó el correo de verificación. Nadie recibió nada. Compruebe quién es por otra vía y después use “Marcar correo como verificado”.',
    'verification_handed' => 'Correo de verificación entregado al servicio de correo (:mailer).',
    'no_reset_link' => 'No se creó ningún enlace de restablecimiento: :status',
    'reset_discarded' => 'NO ENTREGADO: se creó un enlace de restablecimiento, pero el correo está en “:mailer”, así que se descartó el mensaje. Nadie recibió nada.',
    'reset_handed' => 'Correo de restablecimiento de contraseña entregado al servicio de correo (:mailer).',
    'verification_not_sent_undeliverable' => 'NO ENVIADO: esta dirección nunca puede recibir correo (:reason), así que el correo de verificación no se entregó al servicio de correo. Nadie recibió nada.',
    'reset_not_sent_undeliverable' => 'NO ENVIADO: esta dirección nunca puede recibir correo (:reason), así que el enlace de restablecimiento no se entregó al servicio de correo. Nadie recibió nada.',
];
