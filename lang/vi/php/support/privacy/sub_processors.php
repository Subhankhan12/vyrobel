<?php

// I18N-01 — vi translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Chưa có hồ sơ lưu',
    'state_switched_on' => 'Hoạt động — đã bật',
    'state_not_active' => 'Không hoạt động',
    'state_configured' => 'Đã cấu hình',
    'hostinger_hosting' => [
        'name' => 'Hostinger (lưu trữ web)',
        'purpose' => 'Vận hành ứng dụng và cơ sở dữ liệu của ứng dụng.',
        'data' => 'Mọi dữ liệu mà sản phẩm lưu trữ — hồ sơ của mọi tài khoản, chỗ nghỉ và khách.',
        'state' => 'Hoạt động — máy chủ chính thức',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Phân phối và lưu vào bộ nhớ đệm trang web công khai, đứng trước máy chủ.',
        'data' => 'Mọi yêu cầu đi qua CDN: địa chỉ IP, URL, cookie đang được truyền.',
        'state' => 'Hoạt động — quan sát thấy trên mọi phản hồi',
    ],
    'google' => [
        'name' => 'Google (đăng nhập)',
        'purpose' => 'Đăng nhập và đăng ký bằng tài khoản Google.',
        'data' => 'Danh tính Google của người dùng: ID tài khoản, email, tên.',
    ],
    'x' => [
        'name' => 'X (đăng nhập)',
        'purpose' => 'Đăng nhập và đăng ký bằng tài khoản X.',
        'data' => 'Danh tính X của người dùng: ID tài khoản, tên tài khoản (handle), tên.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (email)',
        'purpose' => 'Gửi mọi email giao dịch, từ hộp thư vyrobel.com.',
        'data' => 'Địa chỉ người nhận và nội dung thư — tên khách, mã đặt phòng, ngày.',
        'state_active' => 'Hoạt động — trình gửi thư đang dùng',
        'state_inactive' => 'Không hoạt động — email đang ở chế độ “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (thanh toán & tính phí)',
        'purpose' => 'Tính phí gói đăng ký, thanh toán thẻ của khách, chi trả cho chủ sở hữu (Connect).',
        'data' => 'Thông tin liên hệ xuất hóa đơn, số tiền, mã đặt phòng; với chi trả cho chủ sở hữu, thêm thông tin danh tính để xác minh KYC.',
        'state_fake' => 'Không hoạt động — đang dùng ứng dụng khách giả lập',
    ],
    'anthropic' => [
        'name' => 'Anthropic (trợ lý AI)',
        'purpose' => 'Trả lời câu hỏi trong trợ lý AI.',
        'data' => 'Câu hỏi được đặt ra và ngữ cảnh gửi kèm.',
        'state_off' => 'Không hoạt động — dùng ứng dụng khách giả lập hoặc chưa có khóa',
    ],
    'channex' => [
        'name' => 'Channex (quản lý kênh)',
        'purpose' => 'Đồng bộ giá, tình trạng phòng trống và đặt phòng với các OTA.',
        'data' => 'Thông tin đặt phòng và khách được trao đổi với các kênh.',
        'state_off' => 'Chưa kết nối',
    ],
];
