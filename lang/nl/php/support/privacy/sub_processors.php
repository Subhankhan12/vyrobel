<?php

// I18N-01 — nl translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Geen vastgelegd',
    'state_switched_on' => 'Actief — ingeschakeld',
    'state_not_active' => 'Niet actief',
    'state_configured' => 'Geconfigureerd',
    'hostinger_hosting' => [
        'name' => 'Hostinger (webhosting)',
        'purpose' => 'Draait de applicatie en de bijbehorende database.',
        'data' => 'Alles wat het product opslaat — de gegevens van elk account, elk hotel en elke gast.',
        'state' => 'Actief — de productiehost',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Levert de openbare site uit en cachet die, vóór de host.',
        'data' => 'Elk verzoek dat erdoorheen gaat: IP-adressen, URL’s, cookies tijdens het transport.',
        'state' => 'Actief — waargenomen bij elke respons',
    ],
    'google' => [
        'name' => 'Google (inloggen)',
        'purpose' => 'Inloggen en registreren met een Google-account.',
        'data' => 'De Google-identiteit van de persoon: account-id, e-mailadres, naam.',
    ],
    'x' => [
        'name' => 'X (inloggen)',
        'purpose' => 'Inloggen en registreren met een X-account.',
        'data' => 'De X-identiteit van de persoon: account-id, gebruikersnaam, naam.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Verstuurt elke transactionele e-mail, vanuit de mailbox van vyrobel.com.',
        'data' => 'Adressen van ontvangers en de inhoud van berichten — gastnamen, reserveringsnummers, data.',
        'state_active' => 'Actief — de mailer',
        'state_inactive' => 'Niet actief — e-mail staat op “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (betalingen en facturatie)',
        'purpose' => 'Abonnementsfacturatie, kaartbetalingen van gasten, uitbetalingen aan eigenaren (Connect).',
        'data' => 'Contactpersonen voor facturatie, bedragen, reserveringsnummers; bij uitbetalingen aan eigenaren de identiteitsgegevens voor KYC.',
        'state_fake' => 'Niet actief — er is een nepclient in gebruik',
    ],
    'anthropic' => [
        'name' => 'Anthropic (AI-assistent)',
        'purpose' => 'Beantwoordt vragen in de AI-assistent.',
        'data' => 'De gestelde vraag en de context die daarbij wordt meegestuurd.',
        'state_off' => 'Niet actief — nepclient of geen sleutel',
    ],
    'channex' => [
        'name' => 'Channex (channelmanager)',
        'purpose' => 'Synchroniseert tarieven, beschikbaarheid en reserveringen met OTA’s.',
        'data' => 'Reserverings- en gastgegevens die met kanalen worden uitgewisseld.',
        'state_off' => 'Niet gekoppeld',
    ],
];
