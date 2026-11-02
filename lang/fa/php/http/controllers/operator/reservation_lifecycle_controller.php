<?php

// I18N-01 — fa translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'فقط رزرو موقت را می‌توان تأیید کرد — وضعیت این رزرو: :status.',
    'hold_confirmed' => 'رزرو موقت تأیید شد — این رزرو اکنون یک اقامت تأییدشده است.',
    'cannot_cancel_closed' => 'این رزرو قابل لغو نیست — از قبل بسته شده است.',
    'reason_too_long' => 'دلیل لغو بیش از حد طولانی است — حداکثر 255 کاراکتر.',
    'cancel_rejected' => 'لغو این رزرو ممکن نشد — سرویس رزرو آن را رد کرد. چیزی تغییر نکرد.',
    'cancelled_refunded' => 'رزرو لغو شد و مبلغ :amount استرداد شد (در بازه لغو رایگان). شب‌های نگه‌داشته‌شده به تقویم برمی‌گردند.',
    'cancelled' => 'رزرو لغو شد. شب‌های نگه‌داشته‌شده به تقویم برمی‌گردند.',
    'cannot_no_show' => 'ثبت عدم حضور برای این رزرو ممکن نیست — فقط رزرو تأییدشده را می‌توان عدم حضور ثبت کرد.',
    'no_show_rejected' => 'ثبت عدم حضور برای این رزرو ممکن نشد — سرویس رزرو آن را رد کرد. چیزی تغییر نکرد.',
    'marked_no_show' => 'عدم حضور ثبت شد. شب‌های نگه‌داشته‌شده به تقویم برمی‌گردند.',
];
