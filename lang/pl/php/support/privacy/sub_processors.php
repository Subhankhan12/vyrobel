<?php

// I18N-01 — pl translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Brak w dokumentacji',
    'state_switched_on' => 'Aktywny – włączony',
    'state_not_active' => 'Nieaktywny',
    'state_configured' => 'Skonfigurowany',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hosting)',
        'purpose' => 'Uruchamia aplikację i jej bazę danych.',
        'data' => 'Wszystko, co przechowuje system – dane każdego konta, hotelu i gościa.',
        'state' => 'Aktywny – serwer produkcyjny',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Udostępnia i buforuje publiczną witrynę, działając przed serwerem hostingowym.',
        'data' => 'Każde żądanie, które przez niego przechodzi: adresy IP, adresy URL, pliki cookie w trakcie przesyłania.',
        'state' => 'Aktywny – obserwowany w każdej odpowiedzi',
    ],
    'google' => [
        'name' => 'Google (logowanie)',
        'purpose' => 'Logowanie i rejestracja za pomocą konta Google.',
        'data' => 'Tożsamość osoby z konta Google: identyfikator konta, adres e-mail, imię i nazwisko.',
    ],
    'x' => [
        'name' => 'X (logowanie)',
        'purpose' => 'Logowanie i rejestracja za pomocą konta X.',
        'data' => 'Tożsamość osoby z konta X: identyfikator konta, nazwa użytkownika, imię i nazwisko.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Dostarcza wszystkie e-maile transakcyjne, wysyłane ze skrzynki pocztowej vyrobel.com.',
        'data' => 'Adresy odbiorców i treść wiadomości – imiona i nazwiska gości, numery rezerwacji, daty.',
        'state_active' => 'Aktywny – sterownik poczty',
        'state_inactive' => 'Nieaktywny – poczta działa w trybie „:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (płatności i rozliczenia)',
        'purpose' => 'Rozliczanie subskrypcji, płatności kartą gości, wypłaty dla właścicieli (Connect).',
        'data' => 'Dane kontaktowe do rozliczeń, kwoty, numery rezerwacji; w przypadku wypłat dla właścicieli – dane tożsamości do weryfikacji KYC.',
        'state_fake' => 'Nieaktywny – używana jest atrapa klienta',
    ],
    'anthropic' => [
        'name' => 'Anthropic (asystent AI)',
        'purpose' => 'Odpowiada na pytania w asystencie AI.',
        'data' => 'Zadane pytanie i kontekst przesłany razem z nim.',
        'state_off' => 'Nieaktywny – atrapa klienta lub brak klucza',
    ],
    'channex' => [
        'name' => 'Channex (Channel manager)',
        'purpose' => 'Synchronizuje ceny, dostępność i rezerwacje z portalami rezerwacyjnymi (OTA).',
        'data' => 'Dane rezerwacji i gości wymieniane z kanałami sprzedaży.',
        'state_off' => 'Niepołączony',
    ],
];
