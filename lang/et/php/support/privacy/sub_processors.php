<?php

// I18N-01 — et translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Pole salvestatud',
    'state_switched_on' => 'Aktiivne — sisse lülitatud',
    'state_not_active' => 'Mitteaktiivne',
    'state_configured' => 'Seadistatud',
    'hostinger_hosting' => [
        'name' => 'Hostinger (veebimajutus)',
        'purpose' => 'Käitab rakendust ja selle andmebaasi.',
        'data' => 'Kõik, mida toode salvestab — iga konto, hotelli ja külalise kirje.',
        'state' => 'Aktiivne — töötav server',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Edastab avalikku saiti ja hoiab seda vahemälus serveri ees.',
        'data' => 'Iga selle kaudu liikuv päring: IP-aadressid, URL-id, edastatavad küpsised.',
        'state' => 'Aktiivne — täheldatud igas vastuses',
    ],
    'google' => [
        'name' => 'Google (sisselogimine)',
        'purpose' => 'Sisselogimine ja registreerumine Google’i kontoga.',
        'data' => 'Isiku Google’i identiteet: konto ID, e-posti aadress, nimi.',
    ],
    'x' => [
        'name' => 'X (sisselogimine)',
        'purpose' => 'Sisselogimine ja registreerumine X-i kontoga.',
        'data' => 'Isiku X-i identiteet: konto ID, kasutajanimi, nimi.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-post)',
        'purpose' => 'Edastab kõik tehingukirjad vyrobel.com e-postkastist.',
        'data' => 'Saajate aadressid ja kirjade sisu — külaliste nimed, broneeringu numbrid, kuupäevad.',
        'state_active' => 'Aktiivne — e-posti saatja',
        'state_inactive' => 'Mitteaktiivne — e-posti saatja on „:mailer“',
    ],
    'stripe' => [
        'name' => 'Stripe (maksed ja arveldus)',
        'purpose' => 'Abonementide arveldus, külaliste kaardimaksed, omanike väljamaksed (Connect).',
        'data' => 'Arvelduskontaktid, summad, broneeringu numbrid; omanike väljamaksete puhul KYC jaoks isikusamasuse andmed.',
        'state_fake' => 'Mitteaktiivne — kasutusel on simuleeritud klient',
    ],
    'anthropic' => [
        'name' => 'Anthropic (tehisintellekti assistent)',
        'purpose' => 'Vastab küsimustele tehisintellekti assistendis.',
        'data' => 'Esitatud küsimus ja sellega kaasa saadetud kontekst.',
        'state_off' => 'Mitteaktiivne — simuleeritud klient või võti puudub',
    ],
    'channex' => [
        'name' => 'Channex (kanalihaldur)',
        'purpose' => 'Sünkroonib hinnad, saadavuse ja broneeringud veebipõhiste reisibüroodega (OTA).',
        'data' => 'Müügikanalitega vahetatavad broneeringu- ja külaliseandmed.',
        'state_off' => 'Ühendamata',
    ],
];
