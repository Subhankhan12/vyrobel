<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Chỉ có thể xác nhận đặt phòng giữ chỗ — đặt phòng này đang ở trạng thái “:status”.',
    'hold_confirmed' => 'Đã xác nhận giữ chỗ — đặt phòng này giờ là kỳ lưu trú đã xác nhận.',
    'cannot_cancel_closed' => 'Không thể hủy đặt phòng đó — đặt phòng đã đóng.',
    'reason_too_long' => 'Lý do hủy quá dài — tối đa 255 ký tự.',
    'cancel_rejected' => 'Không thể hủy đặt phòng đó — dịch vụ đặt phòng đã từ chối. Không có gì thay đổi.',
    'cancelled_refunded' => 'Đã hủy đặt phòng + hoàn tiền :amount (trong thời hạn hủy miễn phí). Các đêm đã giữ đã mở lại trên lịch.',
    'cancelled' => 'Đã hủy đặt phòng. Các đêm đã giữ đã mở lại trên lịch.',
    'cannot_no_show' => 'Không thể đánh dấu không đến cho đặt phòng đó — chỉ đặt phòng đã xác nhận mới được đánh dấu.',
    'no_show_rejected' => 'Không thể đánh dấu không đến cho đặt phòng đó — dịch vụ đặt phòng đã từ chối. Không có gì thay đổi.',
    'marked_no_show' => 'Đã đánh dấu không đến. Các đêm đã giữ đã mở lại trên lịch.',
];
