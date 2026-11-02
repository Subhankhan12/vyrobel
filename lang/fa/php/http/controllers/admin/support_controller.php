<?php

// I18N-01 — fa translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'این اتصال دیگر وجود ندارد.',
    'unlink_refused_only_way_in' => 'انجام نشد — این تنها راه ورود این فرد است (بدون رمز عبور و بدون سرویس ورود دیگر). لغو اتصال آن او را برای همیشه از ورود محروم می‌کند.',
    'unlinked' => 'اتصال :provider لغو شد — پس از بازخوانی تأیید شد: اتصال دیگر وجود ندارد.',
    'unlink_not_landed' => 'لغو اتصال انجام نشد — اتصال هنوز وجود دارد.',
    'already_verified' => 'قبلاً تأیید شده است.',
    'marked_verified' => 'به‌عنوان تأییدشده ثبت شد — از پایگاه داده دوباره خوانده شد: :stored به وقت UTC.',
    'verify_not_landed' => 'ثبت تغییر انجام نشد — email_verified_at هنوز خالی است.',
    'already_verified_nothing_to_resend' => 'قبلاً تأیید شده است — چیزی برای ارسال دوباره وجود ندارد.',
    'verification_discarded' => 'تحویل داده نشد — سرویس ایمیل «:mailer» است، بنابراین ایمیل تأیید دور ریخته شد. هیچ‌کس چیزی دریافت نکرد. هویت او را از راه دیگری تأیید کنید و سپس از «ثبت تأیید ایمیل» استفاده کنید.',
    'verification_handed' => 'ایمیل تأیید به سرویس ایمیل (:mailer) تحویل داده شد.',
    'no_reset_link' => 'پیوند بازنشانی ایجاد نشد: :status',
    'reset_discarded' => 'تحویل داده نشد — پیوند بازنشانی ایجاد شد، اما سرویس ایمیل «:mailer» است و ایمیل دور ریخته شد. هیچ‌کس چیزی دریافت نکرد.',
    'reset_handed' => 'ایمیل بازنشانی رمز عبور به سرویس ایمیل (:mailer) تحویل داده شد.',
    'verification_not_sent_undeliverable' => 'ارسال نشد — این نشانی هرگز ایمیل دریافت نمی‌کند (:reason)، بنابراین ایمیل تأیید به سرویس ایمیل تحویل داده نشد. هیچ‌کس چیزی دریافت نکرد.',
    'reset_not_sent_undeliverable' => 'ارسال نشد — این نشانی هرگز ایمیل دریافت نمی‌کند (:reason)، بنابراین پیوند بازنشانی به سرویس ایمیل تحویل داده نشد. هیچ‌کس چیزی دریافت نکرد.',
];
