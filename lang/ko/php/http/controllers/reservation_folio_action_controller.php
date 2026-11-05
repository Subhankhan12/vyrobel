<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => '카드 결제는 Stripe(테스트 모드)를 통해 처리되며 라이브 키로 전환한 후 사용할 수 있습니다. 지금 현장 결제를 받으려면 현금 또는 계좌 이체로 등록하세요.',
    'payment_amount_invalid' => '0보다 큰 결제 금액을 입력하세요.',
    'payment_recorded' => '결제가 등록되었습니다.',
    'charge_amount_invalid' => '0보다 큰 요금 항목 금액을 입력하세요.',
    'charge_added' => '요금 항목이 추가되었습니다.',
    'refund_only_settled' => '정산 완료된 결제만 환불할 수 있습니다.',
    'already_fully_refunded' => '해당 결제는 이미 전액 환불되었습니다.',
    'refund_amount_invalid' => '0보다 큰 환불 금액을 입력하세요.',
    'refund_exceeds' => '환불이 거부되었습니다. 해당 결제에서 환불 가능한 금액은 :amount :currency만 남아 있습니다.',
    'refund_issued' => '환불이 처리되었습니다.',
    'billed_to_account' => ':account 계정으로 청구했습니다. 결제될 때까지 잔액은 미결제 상태로 남습니다.',
    'returned_to_guest' => '고객 폴리오로 되돌렸습니다.',
];
