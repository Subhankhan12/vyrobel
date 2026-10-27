<?php

// app/Livewire/Operator/ReservationRefund.php — the operator back office's refund composer component.

return [
    // a settled payment's kind, the first word of its card
    'kind_capture' => 'Capture',
    'kind_payment' => 'Payment',
    // a payment's refund state, the flag beside its name
    'state_partly_refunded' => 'Partly refunded',
    'state_fully_refunded' => 'Fully refunded',
    'state_nothing_refundable' => 'Nothing refundable',
    // the gateway the money moved through (brand names stay as they are)
    'gateway_manual' => 'Manual / offline',
    'gateway_bank_transfer' => 'Bank transfer',
    'gateway_ota_collect' => 'Collected by the OTA',
];
