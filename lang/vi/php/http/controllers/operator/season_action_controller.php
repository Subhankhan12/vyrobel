<?php

// I18N-01 — vi translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Vui lòng chọn một gói giá.',
    'name_required' => 'Vui lòng đặt tên cho mùa.',
    'name_too_long' => 'Tên mùa tối đa 80 ký tự.',
    'choose_first_night' => 'Vui lòng chọn đêm đầu tiên.',
    'choose_last_night' => 'Vui lòng chọn đêm cuối cùng.',
    'last_before_first' => 'Đêm cuối cùng không thể trước đêm đầu tiên.',
    'price_required' => 'Vui lòng nhập giá mỗi đêm.',
    'read_only' => 'Tổ chức của bạn hiện ở chế độ chỉ đọc — việc thay đổi giá đang tạm dừng.',
    'price_out_of_range' => 'Vui lòng nhập giá mỗi đêm lớn hơn 0 và không quá 1.000.000.',
    'season_saved' => 'Đã lưu mùa. Xem lại phạm vi ảnh hưởng, rồi áp dụng để ghi giá cho các đêm đó.',
    'already_applied' => 'Mùa này đã được áp dụng nên không thể chỉnh sửa — giá mỗi đêm mà mùa này đã ghi đã thay thế giá cũ, và không có bản ghi nào lưu lại giá trước đó. Thay vào đó, vui lòng tạo một mùa mới cho những đêm đó.',
    'season_updated' => 'Đã cập nhật mùa. Xem lại phạm vi ảnh hưởng, rồi áp dụng để ghi giá cho các đêm đó.',
    'deleted_after_apply' => 'Đã xóa mùa “:season”. ⚠️ Giá mỗi đêm mà mùa này đã áp dụng vẫn giữ nguyên — xóa mùa không khôi phục giá mà mùa đã thay thế. Vui lòng tự đặt giá cho những đêm đó nếu chưa đúng.',
    'deleted_not_applied' => 'Đã xóa mùa “:season”. Mùa này chưa được áp dụng nên không có giá nào thay đổi.',
    'applied' => 'Đã áp dụng mùa :season — đã ghi lại :nights trên :units.',
    'nights_count' => '{1}:count đêm|[0,*]:count đêm',
    'units_count' => '{1}:count phòng|[0,*]:count phòng',
    'overlapping_reapplied' => '{1}Đã áp dụng lại :count mùa chồng lấn theo thứ tự ưu tiên.|[0,*]Đã áp dụng lại :count mùa chồng lấn theo thứ tự ưu tiên.',
    'pick_unit_to_continue' => 'Vui lòng chọn một phòng để tiếp tục.',
    'stay_nights_unpriced' => '{1}:count đêm của kỳ lưu trú vẫn chưa có giá.|[0,*]:count đêm của kỳ lưu trú vẫn chưa có giá.',
];
