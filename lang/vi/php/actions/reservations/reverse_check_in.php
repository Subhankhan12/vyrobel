<?php

// I18N-01 — vi translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Kỳ lưu trú trở về trạng thái đã xác nhận, và phòng vẫn được giữ cho các đêm đó.',
    'history_kept' => 'Lượt nhận phòng vẫn nằm trong lịch sử đặt phòng. Việc đó đã xảy ra, và hồ sơ ghi nhận đúng như vậy — thao tác hoàn tác được ghi thêm bên cạnh.',
    'charges_kept' => '{1}:count khoản phí đã ghi từ khi nhận phòng, tổng cộng :amount, vẫn nằm trên folio. Hoàn tác nhận phòng không làm các khoản đã chi biến mất — nếu các khoản đó cũng ghi nhầm, hãy xóa chúng trên folio.|[0,*]:count khoản phí đã ghi từ khi nhận phòng, tổng cộng :amount, vẫn nằm trên folio. Hoàn tác nhận phòng không làm các khoản đã chi biến mất — nếu các khoản đó cũng ghi nhầm, hãy xóa chúng trên folio.',
    'online_check_in_kept' => 'Phần nhận phòng trực tuyến do khách tự thực hiện vẫn ở trạng thái hoàn tất. Khách thực sự đã điền, và thao tác này không yêu cầu họ điền lại.',
    'already_reversed' => 'Lượt nhận phòng đó vừa được hoàn tác — kỳ lưu trú đã trở về trạng thái đã xác nhận.',
    'refused_not_checked_in' => 'Chỉ kỳ lưu trú đã nhận phòng mới có thể hoàn tác nhận phòng; kỳ lưu trú này đang ở trạng thái “:status”.',
];
