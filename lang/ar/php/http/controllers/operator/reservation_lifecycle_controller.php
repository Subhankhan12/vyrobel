<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'يمكن تأكيد الحجز المبدئي فقط — حالة هذا الحجز: :status.',
    'hold_confirmed' => 'تم تأكيد الحجز المبدئي — أصبح الحجز الآن إقامة مؤكدة.',
    'cannot_cancel_closed' => 'لا يمكن إلغاء هذا الحجز — فهو مغلق بالفعل.',
    'reason_too_long' => 'سبب الإلغاء هذا طويل جدًا — الحد الأقصى 255 حرفًا.',
    'cancel_rejected' => 'تعذر إلغاء هذا الحجز — رفضته خدمة الحجز. لم يتم تغيير أي شيء.',
    'cancelled_refunded' => 'تم إلغاء الحجز واسترداد :amount (ضمن فترة الإلغاء المجاني). أصبحت الليالي المحجوزة متاحة مجددًا في التقويم.',
    'cancelled' => 'تم إلغاء الحجز. أصبحت الليالي المحجوزة متاحة مجددًا في التقويم.',
    'cannot_no_show' => 'لا يمكن تسجيل عدم الحضور لهذا الحجز — يمكن ذلك للحجز المؤكد فقط.',
    'no_show_rejected' => 'تعذر تسجيل عدم الحضور لهذا الحجز — رفضته خدمة الحجز. لم يتم تغيير أي شيء.',
    'marked_no_show' => 'تم تسجيل عدم الحضور. أصبحت الليالي المحجوزة متاحة مجددًا في التقويم.',
];
