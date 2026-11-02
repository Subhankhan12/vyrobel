<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'To połączenie już nie istnieje.',
    'unlink_refused_only_way_in' => 'Odmowa – to jedyny sposób logowania tej osoby (brak hasła, brak innego dostawcy). Odłączenie zablokowałoby jej dostęp na stałe.',
    'unlinked' => 'Odłączono dostawcę :provider – odczytano ponownie: połączenie już nie istnieje.',
    'unlink_not_landed' => 'Odłączenie nie zostało zapisane – połączenie nadal istnieje.',
    'already_verified' => 'E-mail jest już zweryfikowany.',
    'marked_verified' => 'Oznaczono jako zweryfikowany – odczytano ponownie z bazy danych: :stored UTC.',
    'verify_not_landed' => 'Zapis nie trafił do bazy danych – email_verified_at nadal jest puste.',
    'already_verified_nothing_to_resend' => 'E-mail jest już zweryfikowany – nie ma czego wysyłać ponownie.',
    'verification_discarded' => 'NIE DOSTARCZONO – poczta działa w trybie „:mailer”, więc e-mail weryfikacyjny został odrzucony. Nikt nic nie otrzymał. Potwierdź tożsamość tej osoby w inny sposób, a potem użyj opcji „Oznacz e-mail jako zweryfikowany”.',
    'verification_handed' => 'E-mail weryfikacyjny przekazano do wysyłki (:mailer).',
    'no_reset_link' => 'Nie utworzono linku do resetowania hasła: :status',
    'reset_discarded' => 'NIE DOSTARCZONO – link do resetowania hasła został utworzony, ale poczta działa w trybie „:mailer”, więc e-mail został odrzucony. Nikt nic nie otrzymał.',
    'reset_handed' => 'E-mail z linkiem do resetowania hasła przekazano do wysyłki (:mailer).',
    'verification_not_sent_undeliverable' => 'NIE WYSŁANO – na ten adres nie można dostarczyć żadnej poczty (:reason), więc e-mail weryfikacyjny nie został przekazany do wysyłki. Nikt nic nie otrzymał.',
    'reset_not_sent_undeliverable' => 'NIE WYSŁANO – na ten adres nie można dostarczyć żadnej poczty (:reason), więc link do resetowania hasła nie został przekazany do wysyłki. Nikt nic nie otrzymał.',
];
