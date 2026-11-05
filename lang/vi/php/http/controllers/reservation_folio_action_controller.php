<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Thanh toán thẻ được xử lý qua Stripe (chế độ kiểm thử) — sẽ khả dụng khi chuyển sang khóa chế độ thực. Vui lòng ghi nhận tiền mặt hoặc chuyển khoản ngân hàng để nhận thanh toán tại quầy ngay bây giờ.',
    'payment_amount_invalid' => 'Vui lòng nhập số tiền thanh toán lớn hơn 0.',
    'payment_recorded' => 'Đã ghi nhận thanh toán.',
    'charge_amount_invalid' => 'Vui lòng nhập số tiền khoản phí lớn hơn 0.',
    'charge_added' => 'Đã thêm khoản phí.',
    'refund_only_settled' => 'Chỉ có thể hoàn tiền cho khoản thanh toán đã hoàn tất.',
    'already_fully_refunded' => 'Khoản thanh toán này đã được hoàn tiền toàn bộ.',
    'refund_amount_invalid' => 'Vui lòng nhập số tiền hoàn lớn hơn 0.',
    'refund_exceeds' => 'Đã từ chối hoàn tiền — khoản thanh toán này chỉ còn có thể hoàn :amount :currency.',
    'refund_issued' => 'Đã hoàn tiền.',
    'billed_to_account' => 'Đã ghi nợ vào tài khoản :account. Số dư vẫn được ghi nợ cho đến khi bên đó thanh toán.',
    'returned_to_guest' => 'Đã chuyển lại vào folio của khách.',
];
