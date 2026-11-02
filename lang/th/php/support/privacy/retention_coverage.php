<?php

// I18N-01 — th translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count วัน (RetentionPolicy, retention:prune)|[0,*]:count วัน (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count วัน (platform:prune-activity)|[0,*]:count วัน (platform:prune-activity)',
    'rule_guest_documents' => '30 วันหลังเช็กเอาต์ (guest-documents:purge-expired)',
    'reason_not_deployed' => 'ทะเบียนข้อมูลที่ใช้นับ (:file) ไม่ได้ติดตั้งไว้บนโฮสต์นี้ จึงไม่สามารถนับหมวดหมู่ที่นี่ได้',
    'reason_unreadable' => 'ไม่สามารถอ่านทะเบียนข้อมูลที่ใช้นับ (:file) ได้ จึงไม่สามารถนับหมวดหมู่ที่นี่ได้',
];
