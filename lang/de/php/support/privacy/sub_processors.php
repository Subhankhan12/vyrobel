<?php

// I18N-01 — de translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Keine hinterlegt',
    'state_switched_on' => 'Aktiv — eingeschaltet',
    'state_not_active' => 'Nicht aktiv',
    'state_configured' => 'Eingerichtet',
    'hostinger_hosting' => [
        'name' => 'Hostinger (Hosting)',
        'purpose' => 'Betreibt die Anwendung und ihre Datenbank.',
        'data' => 'Alles, was das Produkt speichert — alle Konten sowie alle Hotel- und Gästedatensätze.',
        'state' => 'Aktiv — der Produktionsserver',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Liefert die öffentliche Website vor dem Host aus und speichert sie zwischen.',
        'data' => 'Jede Anfrage, die darüber läuft: IP-Adressen, URLs, Cookies während der Übertragung.',
        'state' => 'Aktiv — in jeder Antwort festgestellt',
    ],
    'google' => [
        'name' => 'Google (Anmeldung)',
        'purpose' => 'Anmeldung und Registrierung mit einem Google-Konto.',
        'data' => 'Die Google-Identität der Person: Konto-ID, E-Mail-Adresse, Name.',
    ],
    'x' => [
        'name' => 'X (Anmeldung)',
        'purpose' => 'Anmeldung und Registrierung mit einem X-Konto.',
        'data' => 'Die X-Identität der Person: Konto-ID, Benutzername, Name.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (E-Mail)',
        'purpose' => 'Stellt alle Transaktions-E-Mails vom Postfach vyrobel.com aus zu.',
        'data' => 'Empfängeradressen und Nachrichteninhalte — Gästenamen, Buchungsnummern, Reisedaten.',
        'state_active' => 'Aktiv — der Mailversand',
        'state_inactive' => 'Nicht aktiv — der E-Mail-Versand ist „:mailer“',
    ],
    'stripe' => [
        'name' => 'Stripe (Zahlungen & Abrechnung)',
        'purpose' => 'Abrechnung der Abonnements, Kartenzahlungen von Gästen, Auszahlungen an Eigentümer (Connect).',
        'data' => 'Rechnungskontakte, Beträge, Buchungsnummern; bei Auszahlungen an Eigentümer Identitätsdaten für KYC.',
        'state_fake' => 'Nicht aktiv — ein Fake-Client ist eingebunden',
    ],
    'anthropic' => [
        'name' => 'Anthropic (KI-Assistent)',
        'purpose' => 'Beantwortet Fragen im KI-Assistenten.',
        'data' => 'Die gestellte Frage und der mitgesendete Kontext.',
        'state_off' => 'Nicht aktiv — Fake-Client oder kein Schlüssel',
    ],
    'channex' => [
        'name' => 'Channex (Channel-Manager)',
        'purpose' => 'Synchronisiert Preise, Verfügbarkeit und Buchungen mit OTAs.',
        'data' => 'Buchungs- und Gästedaten, die mit Kanälen ausgetauscht werden.',
        'state_off' => 'Nicht verbunden',
    ],
];
