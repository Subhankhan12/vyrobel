<?php

// I18N-01 — sq translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Asnjë në dosje',
    'state_switched_on' => 'Aktiv — ndezur',
    'state_not_active' => 'Joaktiv',
    'state_configured' => 'Konfiguruar',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Ekzekuton aplikacionin dhe bazën e tij të të dhënave.',
        'data' => 'Gjithçka që ruan produkti — çdo llogari, çdo regjistrim hoteli dhe çdo regjistrim vizitori.',
        'state' => 'Aktiv — serveri i prodhimit',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Shpërndan dhe ruan në memorie të përkohshme faqen publike, përpara serverit.',
        'data' => 'Çdo kërkesë që kalon përmes tij: adresat IP, URL-të, cookie-t gjatë kalimit.',
        'state' => 'Aktiv — vërehet në çdo përgjigje',
    ],
    'google' => [
        'name' => 'Google (hyrja)',
        'purpose' => 'Hyrja dhe regjistrimi me një llogari Google.',
        'data' => 'Identiteti Google i personit: ID-ja e llogarisë, email-i, emri.',
    ],
    'x' => [
        'name' => 'X (hyrja)',
        'purpose' => 'Hyrja dhe regjistrimi me një llogari X.',
        'data' => 'Identiteti X i personit: ID-ja e llogarisë, emri i përdoruesit, emri.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (email)',
        'purpose' => 'Dërgon çdo email transaksional nga kutia postare vyrobel.com.',
        'data' => 'Adresat e marrësve dhe përmbajtja e mesazheve — emrat e vizitorëve, referencat e rezervimeve, datat.',
        'state_active' => 'Aktiv — shërbimi i postës',
        'state_inactive' => 'Joaktiv — posta është «:mailer»',
    ],
    'stripe' => [
        'name' => 'Stripe (pagesat dhe faturimi)',
        'purpose' => 'Faturimi i abonimeve, pagesat me kartë të vizitorëve, derdhjet për pronarët (Connect).',
        'data' => 'Kontaktet për faturim, shumat, referencat e rezervimeve; për derdhjet e pronarëve, identiteti për KYC.',
        'state_fake' => 'Joaktiv — është lidhur një klient i simuluar',
    ],
    'anthropic' => [
        'name' => 'Anthropic (asistenti AI)',
        'purpose' => 'Përgjigjet pyetjeve në asistentin AI.',
        'data' => 'Pyetja e bërë dhe konteksti i dërguar bashkë me të.',
        'state_off' => 'Joaktiv — klient i simuluar ose pa çelës',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Sinkronizon tarifat, disponueshmërinë dhe rezervimet me OTA-të.',
        'data' => 'Të dhënat e rezervimeve dhe të vizitorëve që këmbehen me kanalet.',
        'state_off' => 'Nuk është lidhur',
    ],
];
