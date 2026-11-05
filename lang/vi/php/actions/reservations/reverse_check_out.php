<?php

// I18N-01 — vi translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Kỳ lưu trú trở về trạng thái đã nhận phòng.',
    'pending_tasks_removed' => '{1}:count công việc dọn phòng khi khách đi sẽ bị xóa — chưa bắt đầu.|[0,*]:count công việc dọn phòng khi khách đi sẽ bị xóa — chưa bắt đầu.',
    'started_tasks_kept' => '{1}:count công việc dọn phòng đã được bắt đầu và sẽ được giữ nguyên — hủy công việc mà ai đó đã bắt đầu là quyết định của bộ phận buồng phòng, không phải một thao tác hoàn tác.|[0,*]:count công việc dọn phòng đã được bắt đầu và sẽ được giữ nguyên — hủy công việc mà ai đó đã bắt đầu là quyết định của bộ phận buồng phòng, không phải một thao tác hoàn tác.',
    'unit_stays_dirty' => 'Phòng vẫn được đánh dấu là bẩn. Khách đã quay lại phòng, nên trạng thái “Sạch” có thể không đúng.',
    'notifications_stay_delivered' => 'Các thông báo đã gửi đến nhân viên và khách vẫn giữ nguyên trạng thái đã gửi.',
    'review_unknown' => 'Chúng tôi không có dữ liệu về việc yêu cầu đánh giá có được gửi cho kỳ lưu trú này hay không, vì khách đã trả phòng trước khi hệ thống bắt đầu ghi nhận. Yêu cầu có thể đã được gửi hoặc chưa.',
    'review_sent_on' => 'Yêu cầu đánh giá đã được gửi qua email cho khách vào :date. Không thể thu hồi.',
    'review_sent' => 'Yêu cầu đánh giá đã được gửi qua email cho khách. Không thể thu hồi.',
    'review_suppressed_by_operator' => 'Chưa gửi yêu cầu đánh giá — chỗ nghỉ này đã tắt email yêu cầu đánh giá.',
    'review_suppressed_by_guest' => 'Chưa gửi yêu cầu đánh giá — khách đã yêu cầu không nhận email đánh giá.',
    'review_not_sent' => 'Chưa gửi yêu cầu đánh giá — không có địa chỉ liên hệ, hoặc gửi không thành công.',
    'refused_not_checked_out' => 'Chỉ kỳ lưu trú đã trả phòng mới có thể hoàn tác trả phòng; kỳ lưu trú này đang ở trạng thái “:status”.',
];
