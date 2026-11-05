<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Vui lòng nhập địa chỉ IP hoặc khối CIDR hợp lệ (VD: 203.0.113.4 hoặc 203.0.113.0/24).',
    'ip_already_listed' => 'Địa chỉ :ip đã có trong danh sách IP được phép.',
    'allowlist_enabled_auto_added' => 'Đã bật danh sách IP được phép. Chúng tôi cũng đã thêm địa chỉ hiện tại của bạn (:ip) để bạn không tự khóa mình ở ngoài.',
    'ip_added' => 'Đã thêm :ip vào danh sách IP được phép.',
    'remove_would_lock_out' => 'Gỡ :ip sẽ khiến bạn bị khóa ngoài — đây là quy tắc duy nhất cho phép địa chỉ của bạn (:current) truy cập khu vực nhân viên. Vui lòng thêm quy tắc cho địa chỉ của bạn trước, hoặc gỡ các quy tắc khác.',
    'ip_removed_allowlist_off' => 'Đã gỡ :ip — danh sách IP được phép đã tắt: bất kỳ ai có thông tin đăng nhập hợp lệ đều có thể truy cập khu vực nhân viên.',
    'ip_removed' => 'Đã gỡ :ip khỏi danh sách IP được phép.',
    'session_already_ended' => 'Phiên này đã kết thúc.',
    'signed_out_of_device' => 'Đã đăng xuất thiết bị: :device.',
    'trusted_device_revoked' => 'Đã thu hồi thiết bị tin cậy.',
    'csv_title' => 'Bản xuất dữ liệu tài khoản Vyrobel',
    'csv_name' => 'Tên',
    'csv_email' => 'Email',
    'csv_locale' => 'Ngôn ngữ',
    'csv_roles' => 'Vai trò',
    'csv_activity' => 'Hoạt động',
    'csv_when' => 'Thời gian',
    'csv_action' => 'Thao tác',
    'range_this_year' => 'Năm nay',
    'range_custom' => 'Khoảng tùy chỉnh',
    'range_all_time' => 'Toàn bộ thời gian',
];
