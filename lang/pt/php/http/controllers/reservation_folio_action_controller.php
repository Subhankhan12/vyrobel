<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Os pagamentos com cartão passam pela Stripe (modo de teste) — ficam disponíveis quando as chaves reais forem ativadas. Para receber agora um pagamento na receção, registe numerário ou uma transferência bancária.',
    'payment_amount_invalid' => 'Introduza um valor de pagamento superior a zero.',
    'payment_recorded' => 'Pagamento registado.',
    'charge_amount_invalid' => 'Introduza um valor de lançamento superior a zero.',
    'charge_added' => 'Lançamento adicionado.',
    'refund_only_settled' => 'Só é possível reembolsar um pagamento liquidado.',
    'already_fully_refunded' => 'Esse pagamento já foi totalmente reembolsado.',
    'refund_amount_invalid' => 'Introduza um valor de reembolso superior a zero.',
    'refund_exceeds' => 'Esse reembolso foi recusado — desse pagamento só é ainda possível reembolsar :amount :currency.',
    'refund_issued' => 'Reembolso emitido.',
    'billed_to_account' => 'Faturado a :account. O saldo continua em dívida até ser pago.',
    'returned_to_guest' => 'Devolvido ao folio do hóspede.',
];
