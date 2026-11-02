<?php

// I18N-01 — hi translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'कार्ड भुगतान Stripe (टेस्ट मोड) से होते हैं — Stripe के लाइव मोड में आने पर उपलब्ध होंगे। अभी डेस्क पर भुगतान लेने के लिए नकद या बैंक ट्रांसफ़र दर्ज करें।',
    'payment_amount_invalid' => 'शून्य से ज़्यादा भुगतान राशि दर्ज करें।',
    'payment_recorded' => 'भुगतान दर्ज किया गया।',
    'charge_amount_invalid' => 'शून्य से ज़्यादा शुल्क राशि दर्ज करें।',
    'charge_added' => 'शुल्क जोड़ा गया।',
    'refund_only_settled' => 'केवल सेटल हो चुके भुगतान का रिफ़ंड किया जा सकता है।',
    'already_fully_refunded' => 'उस भुगतान का पूरा रिफ़ंड पहले ही हो चुका है।',
    'refund_amount_invalid' => 'शून्य से ज़्यादा रिफ़ंड राशि दर्ज करें।',
    'refund_exceeds' => 'वह रिफ़ंड मना कर दिया गया — उस भुगतान पर अभी केवल :amount :currency रिफ़ंड किया जा सकता है।',
    'refund_issued' => 'रिफ़ंड किया गया।',
    'billed_to_account' => ':account को बिल किया गया। जब तक वे भुगतान नहीं करते, बकाया राशि देय रहती है।',
    'returned_to_guest' => 'मेहमान के फ़ोलियो में वापस किया गया।',
];
