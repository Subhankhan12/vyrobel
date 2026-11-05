<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'يرجى إدخال العمولة بالنسبة المئوية.',
    'commission_range' => 'يجب أن تكون العمولة رقمًا بين 0 و100.',
    'commission_unchanged' => 'لم يتغير شيء — :rate هي بالفعل عمولة :name اعتبارًا من :date.',
    'commission_from_today' => 'عمولة :name هي :rate اعتبارًا من اليوم — إذ لم تكن هناك اتفاقية إدارة سارية.',
    'commission_waiting_cancelled' => 'تبقى عمولة :name عند :rate — ولن يتم تطبيق التغيير المجدول في :date.',
    'commission_from_date' => 'عمولة :name هي :rate اعتبارًا من :date — للحجوزات التي يتم احتسابها من الآن فصاعدًا. وتحتفظ الحجوزات المحتسبة مسبقًا بعمولتها.',
    'payouts_paused' => 'تم إيقاف التحويلات مؤقتًا للمالك :name — لن يتم إرسال أي تحويل إليه حتى الاستئناف.',
    'payouts_resumed' => 'تم استئناف التحويلات للمالك :name.',
    'period_start_required' => 'يرجى اختيار اليوم الأول من الفترة.',
    'period_end_required' => 'يرجى اختيار اليوم الأخير من الفترة.',
    'period_end_after_start' => 'يجب أن تنتهي الفترة في يوم بدايتها أو بعده.',
    'period_not_closed' => 'تعذر إغلاق هذه الفترة: :reason يرجى اختيار فترة تبدأ بعد آخر فترة مقفلة.',
    'period_sealed' => 'لا يمكن إعادة إنشاء كشف حساب معتمد أو مقفل.',
    'period_not_closed_other' => 'تعذر إنشاء كشف الحساب لتلك الفترة.',
    'period_closed' => 'تم إغلاق الفترة — تم اعتماد كشف الحساب رقم :number بمبلغ مستحق قدره :amount. تبقى الإيرادات التي لم يتم تحصيلها من الضيوف بعد خارج الكشف، وستظهر في كشف لاحق.',
    'note_required' => 'يرجى كتابة الملاحظة أولًا.',
    'note_max' => 'الحد الأقصى لطول الملاحظة 2,000 حرف.',
    'note_added' => 'تمت إضافة الملاحظة.',
    'remove_password_required' => 'يرجى إدخال كلمة المرور لإزالة هذا المالك.',
    'password_wrong' => 'كلمة المرور غير صحيحة — يرجى إدخال كلمة المرور المستخدمة لتسجيل الدخول.',
    'confirm_name_required' => 'يرجى كتابة اسم المالك للتأكيد.',
    'confirm_name_mismatch' => 'الاسم المكتوب غير مطابق — لم تتم إزالة المالك.',
    'owner_removed' => 'تمت إزالة :name — تم إلغاء تعيين الوحدات، مع الاحتفاظ بجميع كشوف الحساب والتحويلات لأغراض المحاسبة.',
    'workspace_paused_until' => 'مساحة العمل متوقفة مؤقتًا — تغييرات الملاك للقراءة فقط حتى :date.',
    'workspace_paused_for_now' => 'مساحة العمل متوقفة مؤقتًا — تغييرات الملاك للقراءة فقط حاليًا.',
];
