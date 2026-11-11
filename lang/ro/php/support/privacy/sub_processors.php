<?php

// I18N-01 — ro translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Niciunul înregistrat',
    'state_switched_on' => 'Activ — pornit',
    'state_not_active' => 'Inactiv',
    'state_configured' => 'Configurat',
    'hostinger_hosting' => [
        'name' => 'Hostinger (găzduire)',
        'purpose' => 'Rulează aplicația și baza de date a acesteia.',
        'data' => 'Tot ce stochează produsul — fiecare cont, fiecare fișă de hotel și de oaspete.',
        'state' => 'Activ — serverul de producție',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Livrează și păstrează în cache site-ul public, în fața serverului de găzduire.',
        'data' => 'Fiecare cerere care trece prin el: adrese IP, URL-uri, cookie-uri în tranzit.',
        'state' => 'Activ — observat la fiecare răspuns',
    ],
    'google' => [
        'name' => 'Google (conectare)',
        'purpose' => 'Conectare și creare cont cu un cont Google.',
        'data' => 'Identitatea Google a persoanei: ID de cont, e-mail, nume.',
    ],
    'x' => [
        'name' => 'X (conectare)',
        'purpose' => 'Conectare și creare cont cu un cont X.',
        'data' => 'Identitatea X a persoanei: ID de cont, nume de utilizator, nume.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Livrează toate e-mailurile tranzacționale, din căsuța de e-mail vyrobel.com.',
        'data' => 'Adresele destinatarilor și conținutul mesajelor — numele oaspeților, codurile de rezervare, datele.',
        'state_active' => 'Activ — sistemul de e-mail',
        'state_inactive' => 'Inactiv — e-mailurile sunt setate pe „:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (plăți și facturare)',
        'purpose' => 'Facturarea abonamentelor, plățile cu cardul ale oaspeților, viramentele către proprietari (Connect).',
        'data' => 'Contacte de facturare, sume, coduri de rezervare; pentru viramentele către proprietari, datele de identitate pentru KYC.',
        'state_fake' => 'Inactiv — este folosit un client simulat',
    ],
    'anthropic' => [
        'name' => 'Anthropic (asistent AI)',
        'purpose' => 'Răspunde la întrebări în asistentul AI.',
        'data' => 'Întrebarea adresată și contextul trimis odată cu ea.',
        'state_off' => 'Inactiv — client simulat sau cheie lipsă',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Sincronizează tarifele, disponibilitatea și rezervările cu OTA-urile.',
        'data' => 'Detalii despre rezervări și oaspeți, schimbate cu canalele.',
        'state_off' => 'Neconectat',
    ],
];
