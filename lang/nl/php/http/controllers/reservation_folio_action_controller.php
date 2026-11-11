<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Kaartbetalingen lopen via Stripe (testmodus) — beschikbaar zodra de livesleutel is ingesteld. Registreer contant geld of een bankoverschrijving om nu een betaling aan de receptie te ontvangen.',
    'payment_amount_invalid' => 'Voer een betalingsbedrag groter dan nul in.',
    'payment_recorded' => 'Betaling geregistreerd.',
    'charge_amount_invalid' => 'Voer een bedrag voor de post groter dan nul in.',
    'charge_added' => 'Post toegevoegd.',
    'refund_only_settled' => 'Alleen een voldane betaling kan worden terugbetaald.',
    'already_fully_refunded' => 'Deze betaling is al volledig terugbetaald.',
    'refund_amount_invalid' => 'Voer een terugbetalingsbedrag groter dan nul in.',
    'refund_exceeds' => 'Deze terugbetaling is geweigerd — op die betaling kan nog maar :amount :currency worden terugbetaald.',
    'refund_issued' => 'Terugbetaling uitgevoerd.',
    'billed_to_account' => 'Op rekening gezet bij :account. Het saldo blijft openstaan tot er is betaald.',
    'returned_to_guest' => 'Teruggezet naar de folio van de gast.',
    'hold_released' => 'Pre-autorisatie vrijgegeven — de kaart van de gast is weer vrij en er is niets afgeschreven.',
];
