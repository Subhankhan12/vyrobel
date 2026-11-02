<?php

// I18N-01 — hi translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'सही IP पता या CIDR ब्लॉक दर्ज करें (उदा. 203.0.113.4 या 203.0.113.0/24)।',
    'ip_already_listed' => ':ip पहले से अनुमति सूची में है।',
    'allowlist_enabled_auto_added' => 'IP अनुमति सूची चालू की गई। हमने आपका मौजूदा पता (:ip) भी जोड़ दिया है, ताकि आप खुद लॉक न हो जाएँ।',
    'ip_added' => ':ip अनुमति सूची में जोड़ा गया।',
    'remove_would_lock_out' => ':ip को हटाने से आप लॉक हो जाएँगे — यही अकेला नियम है जो आपके पते (:current) को बैक ऑफ़िस तक पहुँचने देता है। पहले अपने पते के लिए नियम जोड़ें, या बाकी सभी नियम हटा दें।',
    'ip_removed_allowlist_off' => ':ip हटाया गया — अनुमति सूची बंद है: वैध लॉग इन वाला कोई भी व्यक्ति बैक ऑफ़िस तक पहुँच सकता है।',
    'ip_removed' => ':ip अनुमति सूची से हटाया गया।',
    'session_already_ended' => 'वह सेशन पहले ही समाप्त हो चुका है।',
    'signed_out_of_device' => ':device से लॉग आउट किया गया।',
    'trusted_device_revoked' => 'भरोसेमंद डिवाइस वापस ले लिया गया।',
    'csv_title' => 'Vyrobel अकाउंट एक्सपोर्ट',
    'csv_name' => 'नाम',
    'csv_email' => 'ईमेल',
    'csv_locale' => 'भाषा',
    'csv_roles' => 'भूमिकाएँ',
    'csv_activity' => 'गतिविधि',
    'csv_when' => 'कब',
    'csv_action' => 'कार्रवाई',
    'range_this_year' => 'इस साल',
    'range_custom' => 'कस्टम अवधि',
    'range_all_time' => 'पूरा समय',
];
