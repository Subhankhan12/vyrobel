<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Kết nối này không còn tồn tại.',
    'unlink_refused_only_way_in' => 'Bị từ chối — đây là cách duy nhất người này có thể đăng nhập (không có mật khẩu, không có nhà cung cấp khác). Gỡ liên kết sẽ khiến họ bị khóa vĩnh viễn.',
    'unlinked' => 'Đã gỡ liên kết :provider — đọc lại: kết nối đã bị gỡ.',
    'unlink_not_landed' => 'Gỡ liên kết chưa thành công — kết nối vẫn còn.',
    'already_verified' => 'Đã xác minh trước đó.',
    'marked_verified' => 'Đã đánh dấu đã xác minh — đọc lại từ cơ sở dữ liệu: :stored UTC.',
    'verify_not_landed' => 'Dữ liệu chưa được ghi — email_verified_at vẫn trống.',
    'already_verified_nothing_to_resend' => 'Đã xác minh trước đó — không có gì để gửi lại.',
    'verification_discarded' => 'KHÔNG ĐƯỢC GỬI ĐẾN — email đang ở chế độ “:mailer”, nên email xác minh đã bị loại bỏ. Không ai nhận được gì. Hãy xác nhận danh tính của họ bằng cách khác, sau đó dùng “Đánh dấu email đã xác minh”.',
    'verification_handed' => 'Đã chuyển email xác minh cho trình gửi thư (:mailer).',
    'no_reset_link' => 'Chưa tạo liên kết đặt lại: :status',
    'reset_discarded' => 'KHÔNG ĐƯỢC GỬI ĐẾN — đã tạo liên kết đặt lại, nhưng email đang ở chế độ “:mailer”, nên email đã bị loại bỏ. Không ai nhận được gì.',
    'reset_handed' => 'Đã chuyển email đặt lại mật khẩu cho trình gửi thư (:mailer).',
    'verification_not_sent_undeliverable' => 'CHƯA GỬI — địa chỉ này không bao giờ nhận được email (:reason), nên email xác minh chưa được chuyển cho trình gửi thư. Không ai nhận được gì.',
    'reset_not_sent_undeliverable' => 'CHƯA GỬI — địa chỉ này không bao giờ nhận được email (:reason), nên liên kết đặt lại chưa được chuyển cho trình gửi thư. Không ai nhận được gì.',
];
