<?php

// I18N-01 — es translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'No consta ninguno',
    'state_switched_on' => 'Activo: activado',
    'state_not_active' => 'No activo',
    'state_configured' => 'Configurado',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Ejecuta la aplicación y su base de datos.',
        'data' => 'Todo lo que almacena el producto: todas las cuentas y todos los registros de hoteles y de huéspedes.',
        'state' => 'Activo: es el servidor de producción',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Sirve el sitio público y lo almacena en caché, por delante del servidor.',
        'data' => 'Todas las solicitudes que pasan por la CDN: direcciones IP, URL y cookies en tránsito.',
        'state' => 'Activo: se observa en todas las respuestas',
    ],
    'google' => [
        'name' => 'Google (inicio de sesión)',
        'purpose' => 'Inicio de sesión y registro con una cuenta de Google.',
        'data' => 'La identidad de Google de la persona: ID de cuenta, correo electrónico y nombre.',
    ],
    'x' => [
        'name' => 'X (inicio de sesión)',
        'purpose' => 'Inicio de sesión y registro con una cuenta de X.',
        'data' => 'La identidad de X de la persona: ID de cuenta, nombre de usuario y nombre.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (correo electrónico)',
        'purpose' => 'Entrega todos los correos transaccionales, desde el buzón de vyrobel.com.',
        'data' => 'Direcciones de los destinatarios y contenido de los mensajes: nombres de huéspedes, referencias de reservas y fechas.',
        'state_active' => 'Activo: es el servicio de correo',
        'state_inactive' => 'No activo: el correo está en “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (pagos y facturación)',
        'purpose' => 'Facturación de suscripciones, pagos con tarjeta de los huéspedes y liquidaciones de los propietarios (Connect).',
        'data' => 'Contactos de facturación, importes y referencias de reservas; en las liquidaciones de los propietarios, la identidad para la verificación KYC.',
        'state_fake' => 'No activo: hay un cliente simulado vinculado',
    ],
    'anthropic' => [
        'name' => 'Anthropic (asistente de IA)',
        'purpose' => 'Responde a las preguntas que se hacen en el asistente de IA.',
        'data' => 'La pregunta formulada y el contexto que se envía con ella.',
        'state_off' => 'No activo: cliente simulado o sin clave',
    ],
    'channex' => [
        'name' => 'Channex (gestor de canales)',
        'purpose' => 'Sincroniza tarifas, disponibilidad y reservas con las OTA.',
        'data' => 'Datos de reservas y de huéspedes que se intercambian con los canales.',
        'state_off' => 'Sin conectar',
    ],
];
