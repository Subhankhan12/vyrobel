<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Vui lòng nhập mức hoa hồng, tính theo phần trăm.',
    'commission_range' => 'Hoa hồng phải là một số từ 0 đến 100.',
    'commission_unchanged' => 'Không có gì thay đổi — :rate đã là mức hoa hồng của :name từ :date.',
    'commission_from_today' => 'Hoa hồng của :name là :rate từ hôm nay — trước đó chưa có hợp đồng nào có hiệu lực.',
    'commission_waiting_cancelled' => 'Chủ sở hữu :name giữ nguyên mức :rate — thay đổi dự kiến từ :date sẽ không diễn ra.',
    'commission_from_date' => 'Hoa hồng của :name là :rate từ :date — áp dụng cho các đặt phòng được tính từ bây giờ. Đặt phòng đã được tính giữ nguyên mức hoa hồng cũ.',
    'payouts_paused' => 'Đã tạm dừng chi trả cho :name — không có khoản chi trả nào được gửi cho họ cho đến khi bạn tiếp tục.',
    'payouts_resumed' => 'Đã tiếp tục chi trả cho :name.',
    'period_start_required' => 'Vui lòng chọn ngày đầu tiên của kỳ.',
    'period_end_required' => 'Vui lòng chọn ngày cuối cùng của kỳ.',
    'period_end_after_start' => 'Ngày kết thúc kỳ phải trùng hoặc sau ngày bắt đầu.',
    'period_not_closed' => 'Không thể chốt kỳ này: :reason Vui lòng chọn kỳ bắt đầu sau kỳ được niêm phong gần nhất.',
    'period_sealed' => 'Không thể tạo lại bảng đối soát đã chốt hoặc đã niêm phong.',
    'period_not_closed_other' => 'Không thể lập bảng đối soát cho kỳ đó.',
    'period_closed' => 'Đã chốt kỳ — bảng đối soát #:number đã chốt với số tiền phải trả :amount. Doanh thu chưa thu từ khách vẫn chưa được ghi nhận và sẽ xuất hiện trên một bảng đối soát sau.',
    'note_required' => 'Vui lòng viết ghi chú trước.',
    'note_max' => 'Ghi chú tối đa 2.000 ký tự.',
    'note_added' => 'Đã thêm ghi chú.',
    'remove_password_required' => 'Vui lòng nhập mật khẩu để lưu trữ chủ sở hữu này.',
    'password_wrong' => 'Mật khẩu không đúng — vui lòng nhập mật khẩu bạn dùng để đăng nhập.',
    'confirm_name_required' => 'Nhập tên chủ sở hữu để xác nhận.',
    'confirm_name_mismatch' => 'Tên bạn nhập không khớp — chủ sở hữu chưa được lưu trữ.',
    'owner_removed' => 'Đã lưu trữ :name — các phòng không còn thuộc chủ sở hữu nào, mọi bảng đối soát & khoản chi trả được giữ lại cho mục đích kế toán.',
    'workspace_paused_until' => 'Tổ chức đang tạm dừng — phần chủ sở hữu chỉ đọc đến :date.',
    'workspace_paused_for_now' => 'Tổ chức đang tạm dừng — phần chủ sở hữu tạm thời chỉ đọc.',
];
