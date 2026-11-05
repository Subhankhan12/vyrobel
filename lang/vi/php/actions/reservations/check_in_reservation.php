<?php

// I18N-01 — vi translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Không thể nhận phòng cho đặt phòng đã hủy: đặt phòng đã đóng và phòng có thể đã được bán lại. Nếu khách vẫn đến, cần tạo một đặt phòng mới.',
    'refused_no_show' => 'Không thể nhận phòng cho đặt phòng không đến: đặt phòng đã đóng và các đêm đã được mở bán lại. Để xếp phòng cho khách này, vui lòng tạo “Đặt phòng mới” tại lễ tân — hệ thống sẽ kiểm tra lại phòng trống trước khi đặt. Trạng thái không đến và mọi khoản phí đã ghi trên đặt phòng đó vẫn được lưu trong hồ sơ khách.',
    'refused_checked_out' => 'Không thể nhận phòng cho đặt phòng đã trả phòng: việc khách đi đã được ghi nhận và xử lý. Không thể hoàn tác trả phòng.',
    'refused_tentative' => 'Không thể nhận phòng cho đặt phòng giữ chỗ: phòng đang được giữ nhưng chưa được xác nhận, nên chưa ai cam kết kỳ lưu trú này với khách. Vui lòng bấm nút “Xác nhận giữ chỗ” trên đặt phòng này trước, rồi nhận phòng cho khách. Các đêm đã được đặt phòng này giữ, nên việc xác nhận không lấy thêm phòng và không thể thất bại vì thiếu phòng trống.',
    'refused_other' => 'Không thể nhận phòng cho đặt phòng ở trạng thái “:status”.',
];
