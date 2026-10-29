<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'هذا الربط لم يعد موجودًا.',
    'unlink_refused_only_way_in' => 'مرفوض — هذه هي الطريقة الوحيدة لتسجيل دخول هذا الشخص (لا كلمة مرور، ولا مزود آخر). فك الربط سيمنعه من الدخول نهائيًا.',
    'unlinked' => 'تم فك ربط :provider — بعد القراءة مجددًا: الربط لم يعد موجودًا.',
    'unlink_not_landed' => 'لم ينجح فك الربط — لا يزال الربط موجودًا.',
    'already_verified' => 'تم التحقق بالفعل.',
    'marked_verified' => 'تم اعتماد التحقق — بعد القراءة من قاعدة البيانات: :stored UTC.',
    'verify_not_landed' => 'لم تنجح الكتابة — لا يزال email_verified_at فارغًا.',
    'already_verified_nothing_to_resend' => 'تم التحقق بالفعل — لا حاجة لإعادة الإرسال.',
    'verification_discarded' => 'لم يتم التسليم — طريقة إرسال البريد «:mailer»، لذا تم تجاهل رسالة التحقق. لم يستلم أحد أي شيء. يرجى التحقق من هوية الشخص بطريقة أخرى، ثم استخدام «اعتماد البريد الإلكتروني كمتحقق منه».',
    'verification_handed' => 'تم تسليم رسالة التحقق إلى خدمة البريد (:mailer).',
    'no_reset_link' => 'لم يتم إنشاء رابط إعادة تعيين: :status',
    'reset_discarded' => 'لم يتم التسليم — تم إنشاء رابط إعادة تعيين، لكن طريقة إرسال البريد «:mailer»، لذا تم تجاهل الرسالة. لم يستلم أحد أي شيء.',
    'reset_handed' => 'تم تسليم رسالة إعادة تعيين كلمة المرور إلى خدمة البريد (:mailer).',
    'verification_not_sent_undeliverable' => 'لم يتم الإرسال — لا يمكن لهذا العنوان استلام البريد أبدًا (:reason)، لذا لم يتم تسليم رسالة التحقق إلى خدمة البريد. لم يستلم أحد أي شيء.',
    'reset_not_sent_undeliverable' => 'لم يتم الإرسال — لا يمكن لهذا العنوان استلام البريد أبدًا (:reason)، لذا لم يتم تسليم رابط إعادة التعيين إلى خدمة البريد. لم يستلم أحد أي شيء.',
];
