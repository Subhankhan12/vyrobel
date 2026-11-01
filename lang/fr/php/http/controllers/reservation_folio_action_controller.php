<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Les paiements par carte passent par Stripe (mode test) — ils seront disponibles à l’activation des clés réelles. Pour encaisser un paiement à la réception dès maintenant, enregistrez un paiement en espèces ou par virement bancaire.',
    'payment_amount_invalid' => 'Saisissez un montant de paiement supérieur à zéro.',
    'payment_recorded' => 'Paiement enregistré.',
    'charge_amount_invalid' => 'Saisissez un montant de prestation supérieur à zéro.',
    'charge_added' => 'Prestation ajoutée.',
    'refund_only_settled' => 'Seul un paiement encaissé peut être remboursé.',
    'already_fully_refunded' => 'Ce paiement est déjà entièrement remboursé.',
    'refund_amount_invalid' => 'Saisissez un montant de remboursement supérieur à zéro.',
    'refund_exceeds' => 'Remboursement refusé — seuls :amount :currency peuvent encore être remboursés sur ce paiement.',
    'refund_issued' => 'Remboursement effectué.',
    'billed_to_account' => 'Facturé à :account. Le solde reste dû jusqu’à son règlement.',
    'returned_to_guest' => 'Renvoyé sur le folio du client.',
];
