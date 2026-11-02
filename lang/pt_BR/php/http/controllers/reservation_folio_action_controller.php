<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Os pagamentos com cartão passam pela Stripe (modo de teste) — ficam disponíveis quando as chaves de produção forem ativadas. Para receber um pagamento na recepção agora, registre dinheiro ou uma transferência bancária.',
    'payment_amount_invalid' => 'Informe um valor de pagamento maior que zero.',
    'payment_recorded' => 'Pagamento registrado.',
    'charge_amount_invalid' => 'Informe um valor de lançamento maior que zero.',
    'charge_added' => 'Lançamento adicionado.',
    'refund_only_settled' => 'Somente um pagamento quitado pode ser reembolsado.',
    'already_fully_refunded' => 'Esse pagamento já foi totalmente reembolsado.',
    'refund_amount_invalid' => 'Informe um valor de reembolso maior que zero.',
    'refund_exceeds' => 'Esse reembolso foi recusado — desse pagamento, ainda é possível reembolsar somente :amount :currency.',
    'refund_issued' => 'Reembolso emitido.',
    'billed_to_account' => 'Faturado para :account. O saldo continua em aberto até ser pago.',
    'returned_to_guest' => 'Devolvido ao fólio do hóspede.',
];
