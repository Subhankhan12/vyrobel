<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'يرجى إدخال عنوان IP أو نطاق CIDR صالح (مثل 203.0.113.4 أو 203.0.113.0/24).',
    'ip_already_listed' => ':ip موجود بالفعل في القائمة المسموح بها.',
    'allowlist_enabled_auto_added' => 'تم تفعيل قائمة عناوين IP المسموح بها. كما تمت إضافة عنوانك الحالي (:ip) حتى لا تفقد إمكانية الوصول.',
    'ip_added' => 'تمت إضافة :ip إلى القائمة المسموح بها.',
    'remove_would_lock_out' => 'ستؤدي إزالة :ip إلى منعك من الوصول — فهي القاعدة الوحيدة التي تسمح لعنوانك (:current) بالوصول إلى الواجهة الإدارية. يرجى إضافة قاعدة لعنوانك أولًا، أو إزالة القواعد الأخرى.',
    'ip_removed_allowlist_off' => 'تمت إزالة :ip — القائمة المسموح بها متوقفة الآن: يمكن لأي شخص لديه بيانات تسجيل دخول صالحة الوصول إلى الواجهة الإدارية.',
    'ip_removed' => 'تمت إزالة :ip من القائمة المسموح بها.',
    'session_already_ended' => 'انتهت هذه الجلسة بالفعل.',
    'signed_out_of_device' => 'تم تسجيل الخروج من :device.',
    'trusted_device_revoked' => 'تم سحب الثقة من الجهاز.',
    'csv_title' => 'تصدير حساب Vyrobel',
    'csv_name' => 'الاسم',
    'csv_email' => 'البريد الإلكتروني',
    'csv_locale' => 'اللغة',
    'csv_roles' => 'الأدوار',
    'csv_activity' => 'النشاط',
    'csv_when' => 'الوقت',
    'csv_action' => 'الإجراء',
    'range_this_year' => 'هذا العام',
    'range_custom' => 'نطاق مخصص',
    'range_all_time' => 'منذ البداية',
];
