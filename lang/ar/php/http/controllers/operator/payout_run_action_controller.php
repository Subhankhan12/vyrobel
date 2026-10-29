<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'محرك التحويلات معطّل (PAYOUT_ENGINE_ENABLED). يرجى تفعيله قبل تشغيل التحويلات.',
    'nothing_to_run' => 'لا يوجد ما يتم تشغيله — لا يوجد لدى أي مالك هنا كشف حساب مستحق الدفع أو تحويل محتجز:paused_note.',
    'run_complete' => 'اكتملت دورة التحويلات (الوضع: :mode) — المنشأة: :created · المدفوعة: :paid · المحتجزة: :held:paused_note.',
    'create_failed' => 'تعذر إنشاء التحويل — :reason',
    'already_has_payout' => 'يوجد بالفعل تحويل لكشف الحساب هذا — لم يتم إنشاء أي شيء.',
    'one_payout_created' => 'تم إنشاء تحويل واحد.',
    'pay_failed' => 'تعذر الدفع — :reason',
    'one_payout_paid' => 'تم تعليم تحويل واحد على أنه مدفوع.',
    'payout_failed_reason' => 'فشل التحويل — :reason',
    'payout_failed_no_reason' => 'فشل التحويل — لم يقدم مسار الصرف أي سبب.',
    'create_all_failed' => 'تعذر إنشاء التحويلات — :reason',
    'no_payout_created_paused' => 'لم يتم إنشاء أي تحويل:paused_note — يرجى استئناف تحويلاتهم من صفحة المالك.',
    'nothing_ready' => 'لا يوجد ما هو جاهز للدفع — لا يوجد كشف حساب معتمد بصافي مستحق موجب:paused_note.',
    'no_new_payouts' => 'لا توجد تحويلات جديدة — لكل كشف حساب جاهز تحويل بالفعل:paused_note.',
    'payouts_created' => '{0} تم إنشاء :count تحويلات:paused_note.|تم إنشاء تحويل واحد:paused_note.|تم إنشاء تحويلين:paused_note.|تم إنشاء :count تحويلات:paused_note.|تم إنشاء :count تحويلًا:paused_note.|تم إنشاء :count تحويل:paused_note.',
    'some_not_created' => ':message تعذر إنشاء بعضها — :reason',
    'nothing_paid_paused' => 'لم يتم دفع أي شيء:paused_note — يرجى استئناف تحويلاتهم من صفحة المالك.',
    'no_pending_payouts' => 'لا توجد تحويلات قيد الانتظار للدفع.',
    'paid_only' => '{0} تم تعليم :count تحويلات على أنها مدفوعة:paused_note.|تم تعليم تحويل واحد على أنه مدفوع:paused_note.|تم تعليم تحويلين على أنهما مدفوعان:paused_note.|تم تعليم :count تحويلات على أنها مدفوعة:paused_note.|تم تعليم :count تحويلًا على أنها مدفوعة:paused_note.|تم تعليم :count تحويل على أنها مدفوعة:paused_note.',
    'failed_only' => '{0} فشل :count تحويلات عبر مسار الصرف:paused_note.|فشل تحويل واحد عبر مسار الصرف:paused_note.|فشل تحويلان عبر مسار الصرف:paused_note.|فشل :count تحويلات عبر مسار الصرف:paused_note.|فشل :count تحويلًا عبر مسار الصرف:paused_note.|فشل :count تحويل عبر مسار الصرف:paused_note.',
    'paid_and_failed' => '{0} تم تعليم :count تحويلات على أنها مدفوعة · فشل عبر مسار الصرف: :failed:paused_note.|تم تعليم تحويل واحد على أنه مدفوع · فشل عبر مسار الصرف: :failed:paused_note.|تم تعليم تحويلين على أنهما مدفوعان · فشل عبر مسار الصرف: :failed:paused_note.|تم تعليم :count تحويلات على أنها مدفوعة · فشل عبر مسار الصرف: :failed:paused_note.|تم تعليم :count تحويلًا على أنها مدفوعة · فشل عبر مسار الصرف: :failed:paused_note.|تم تعليم :count تحويل على أنها مدفوعة · فشل عبر مسار الصرف: :failed:paused_note.',
    'some_not_attempted' => ':message لم تتم محاولة دفع بعضها — :reason',
    'nothing_payable_negative' => 'لا يوجد مبلغ مستحق — صافي كشف الحساب هذا سالب، لذا يتم ترحيل الرصيد لخصمه من كشف الحساب التالي للمالك.',
    'nothing_payable_zero' => 'لا يوجد مبلغ مستحق — صافي كشف الحساب هذا صفر، لذا لا توجد أي مستحقات.',
    'workspace_paused_until' => 'مساحة العمل متوقفة مؤقتًا — التحويلات للقراءة فقط حتى :date.',
    'workspace_paused_for_now' => 'مساحة العمل متوقفة مؤقتًا — التحويلات للقراءة فقط حاليًا.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{0} تم تخطي :count ملاك متوقفين مؤقتًا|تم تخطي مالك واحد متوقف مؤقتًا|تم تخطي مالكين متوقفين مؤقتًا|تم تخطي :count ملاك متوقفين مؤقتًا|تم تخطي :count مالكًا متوقفًا مؤقتًا|تم تخطي :count مالك متوقف مؤقتًا',
];
