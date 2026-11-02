<?php

// I18N-01 — pl translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Wyłączniki awaryjne',
    'subtitle' => 'Zatrzymanie nowych rejestracji lub logowania przez wybranego dostawcę bez wdrożenia. Każde przełączenie jest audytowane wraz z powodem.',
    'explainer' => 'Każdy wyłącznik jest <strong>włączony</strong>, dopóki ktoś go tu nie wyłączy – także zaraz po wdrożeniu. Żaden z nich nie może wpłynąć na tę konsolę. Wyłączenie <strong>logowania</strong> przez dostawcę blokuje każdego, kto może się zalogować tylko w ten sposób, dlatego te wyłączniki pokazują liczbę takich kont i pytają dwa razy.',
    'state_on' => 'Włączony',
    'state_off' => 'Wyłączony',
    'last_changed_by' => 'Ostatnia zmiana: :time UTC · Administrator: :name.',
    'last_changed' => 'Ostatnia zmiana: :time UTC.',
    'never_changed' => 'Nigdy niezmieniany – domyślnie włączony.',
    'no_account_relies' => 'Żadne konto nie korzysta obecnie wyłącznie z tego dostawcy – każde ma też hasło lub inny sposób logowania.',
    'would_lock_out' => '{1}Wyłączenie zablokuje dostęp do <strong>:count</strong> konta – bez hasła, a ten dostawca to jego jedyny sposób logowania.|Wyłączenie zablokuje dostęp do <strong>:count</strong> kont – bez hasła, a ten dostawca to ich jedyny sposób logowania.|Wyłączenie zablokuje dostęp do <strong>:count</strong> kont – bez hasła, a ten dostawca to ich jedyny sposób logowania.',
    'reason' => 'Powód',
    'why_turn_off' => 'Dlaczego ten wyłącznik jest wyłączany?',
    'why_turn_on' => 'Dlaczego ten wyłącznik jest włączany?',
    'confirm_lockout_zero' => 'Rozumiem, że każdy, kto loguje się tylko w ten sposób, będzie zablokowany od teraz do ponownego włączenia.',
    'confirm_lockout' => '{1}Rozumiem, że to zablokuje :count konto do czasu ponownego włączenia.|Rozumiem, że to zablokuje :count konta do czasu ponownego włączenia.|Rozumiem, że to zablokuje :count kont do czasu ponownego włączenia.',
    'turn_off' => 'Wyłącz',
    'turn_on' => 'Włącz',
];
