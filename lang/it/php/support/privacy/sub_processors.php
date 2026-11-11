<?php

// I18N-01 — it translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Nessuno in archivio',
    'state_switched_on' => 'Attivo — abilitato',
    'state_not_active' => 'Non attivo',
    'state_configured' => 'Configurato',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Esegue l’applicazione e il relativo database.',
        'data' => 'Tutto ciò che il prodotto memorizza — ogni record di account, hotel e ospiti.',
        'state' => 'Attivo — host di produzione',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Distribuisce il sito pubblico e ne tiene una copia in cache, davanti all’host.',
        'data' => 'Ogni richiesta che lo attraversa: indirizzi IP, URL, cookie in transito.',
        'state' => 'Attivo — rilevato su ogni risposta',
    ],
    'google' => [
        'name' => 'Google (accesso)',
        'purpose' => 'Accesso e registrazione con un account Google.',
        'data' => 'L’identità Google della persona: ID dell’account, e-mail, nome.',
    ],
    'x' => [
        'name' => 'X (accesso)',
        'purpose' => 'Accesso e registrazione con un account X.',
        'data' => 'L’identità X della persona: ID dell’account, nome utente, nome.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Recapita ogni e-mail transazionale, dalla casella di posta vyrobel.com.',
        'data' => 'Indirizzi dei destinatari e contenuto dei messaggi — nomi degli ospiti, riferimenti delle prenotazioni, date.',
        'state_active' => 'Attivo — è il sistema di invio',
        'state_inactive' => 'Non attivo — il sistema di invio è «:mailer»',
    ],
    'stripe' => [
        'name' => 'Stripe (pagamenti e fatturazione)',
        'purpose' => 'Fatturazione degli abbonamenti, pagamenti con carta degli ospiti, versamenti ai proprietari (Connect).',
        'data' => 'Contatti di fatturazione, importi, riferimenti delle prenotazioni; per i versamenti ai proprietari, dati di identità per la verifica KYC.',
        'state_fake' => 'Non attivo — è associato un client fittizio',
    ],
    'anthropic' => [
        'name' => 'Anthropic (assistente IA)',
        'purpose' => 'Risponde alle domande nell’assistente IA.',
        'data' => 'La domanda posta e il contesto inviato con essa.',
        'state_off' => 'Non attivo — client fittizio o chiave assente',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Sincronizza tariffe, disponibilità e prenotazioni con le OTA.',
        'data' => 'Dati delle prenotazioni e degli ospiti scambiati con i canali.',
        'state_off' => 'Non connesso',
    ],
];
