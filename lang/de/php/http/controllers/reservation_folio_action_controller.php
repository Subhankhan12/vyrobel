<?php

// I18N-01 — de translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Kartenzahlungen laufen über Stripe (Testmodus) — verfügbar nach der Umstellung auf Live-Schlüssel. Erfassen Sie Bargeld oder eine Banküberweisung, um jetzt eine Zahlung an der Rezeption anzunehmen.',
    'payment_amount_invalid' => 'Geben Sie einen Zahlungsbetrag größer als null ein.',
    'payment_recorded' => 'Zahlung erfasst.',
    'charge_amount_invalid' => 'Geben Sie einen Postenbetrag größer als null ein.',
    'charge_added' => 'Posten hinzugefügt.',
    'refund_only_settled' => 'Nur eine beglichene Zahlung kann erstattet werden.',
    'already_fully_refunded' => 'Diese Zahlung wurde bereits vollständig erstattet.',
    'refund_amount_invalid' => 'Geben Sie einen Erstattungsbetrag größer als null ein.',
    'refund_exceeds' => 'Die Rückerstattung wurde abgelehnt — für diese Zahlung sind nur noch :amount :currency erstattungsfähig.',
    'refund_issued' => 'Rückerstattung ausgeführt.',
    'billed_to_account' => 'Auf :account abgerechnet. Der Saldo bleibt offen, bis die Zahlung eingeht.',
    'returned_to_guest' => 'Zurück auf das Folio des Gastes übertragen.',
];
