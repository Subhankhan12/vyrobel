<?php

// I18N-01 — vi translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Không thể giao phòng cho :owner — họ chưa chấp nhận lời mời, hoặc đã được lưu trữ. Chỉ có thể giao phòng cho chủ sở hữu đang hoạt động.',
    'unit_on_pending_invitation' => 'Phòng “:unit” có trong một lời mời chủ sở hữu chưa được chấp nhận. Phòng sẽ thuộc về chủ sở hữu đó khi họ chấp nhận — hoặc hãy hủy lời mời trước để giao phòng cho người khác.',
    'unit_already_owned' => 'Phòng “:unit” đã thuộc về :owner, nên không thể giao cho người khác ở đây. Chỉ có thể giao phòng chưa có chủ sở hữu.',
    'unit_owned_by_another' => 'chủ sở hữu khác',
    'date_invalid' => 'Vui lòng chọn một ngày hợp lệ làm ngày giao phòng.',
    'date_in_past' => 'Vui lòng chọn hôm nay hoặc một ngày sau đó. Một ngày đã qua sẽ làm thay đổi thu nhập đã được tính.',
    'date_too_far' => 'Vui lòng chọn một ngày trong vòng :days ngày tới.',
    'no_agreement' => 'Chủ sở hữu :owner chưa có hợp đồng quản lý nào áp dụng cho phòng “:unit” vào ngày :date. Hãy lập hợp đồng quản lý cho :owner áp dụng cho phòng này từ ngày :date trước, rồi mới giao phòng cho họ.',
    'stay_across_date' => 'Có khách lưu trú tại phòng “:unit” từ :from đến :to, nên ngày :date nằm giữa kỳ lưu trú này. Vui lòng chọn ngày :to hoặc một ngày sau đó, để không có kỳ lưu trú nào bị chia giữa bạn và :owner.',
    'future_stays_with_another_owner' => 'Phòng “:unit” có các kỳ lưu trú từ ngày :date trở đi đã được tính thu nhập cho chủ sở hữu khác (:owners). Các kỳ lưu trú đó vẫn thuộc về họ, nên không thể giao phòng từ ngày đó.',
];
