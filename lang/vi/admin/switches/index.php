<?php

// I18N-01 — vi translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Công tắc khẩn cấp',
    'subtitle' => 'Dừng đăng ký mới, hoặc đăng nhập qua một nhà cung cấp, mà không cần triển khai. Mỗi lần bật/tắt đều được ghi nhật ký kiểm tra kèm lý do.',
    'explainer' => 'Mọi công tắc đều <strong>bật</strong> trừ khi có người tắt nó ở đây — kể cả ngay sau khi triển khai. Không công tắc nào ảnh hưởng đến bảng điều khiển này. Tắt <strong>đăng nhập</strong> qua một nhà cung cấp sẽ khóa bất kỳ ai chỉ có cách đăng nhập đó, nên các công tắc này hiển thị số lượng và hỏi xác nhận hai lần.',
    'state_on' => 'Bật',
    'state_off' => 'Tắt',
    'last_changed_by' => 'Thay đổi lần cuối lúc :time UTC bởi :name.',
    'last_changed' => 'Thay đổi lần cuối lúc :time UTC.',
    'never_changed' => 'Chưa từng thay đổi — mặc định bật.',
    'no_account_relies' => 'Hiện không có tài khoản nào chỉ dựa vào nhà cung cấp này — tài khoản nào cũng có mật khẩu hoặc cách đăng nhập khác.',
    'would_lock_out' => '{1}Tắt công tắc này sẽ khóa <strong>:count</strong> tài khoản — không có mật khẩu, và nhà cung cấp này là cách đăng nhập duy nhất của họ.|[0,*]Tắt công tắc này sẽ khóa <strong>:count</strong> tài khoản — không có mật khẩu, và nhà cung cấp này là cách đăng nhập duy nhất của họ.',
    'reason' => 'Lý do',
    'why_turn_off' => 'Vì sao bạn tắt công tắc này?',
    'why_turn_on' => 'Vì sao bạn bật công tắc này?',
    'confirm_lockout_zero' => 'Tôi hiểu rằng bất kỳ ai chỉ đăng nhập theo cách này sẽ bị khóa từ bây giờ cho đến khi công tắc được bật lại.',
    'confirm_lockout' => '{1}Tôi hiểu rằng thao tác này khóa :count tài khoản cho đến khi công tắc được bật lại.|[0,*]Tôi hiểu rằng thao tác này khóa :count tài khoản cho đến khi công tắc được bật lại.',
    'turn_off' => 'Tắt',
    'turn_on' => 'Bật',
];
