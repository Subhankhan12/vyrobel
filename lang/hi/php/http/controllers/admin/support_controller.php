<?php

// I18N-01 — hi translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'वह कनेक्शन अब मौजूद नहीं है।',
    'unlink_refused_only_way_in' => 'मना किया गया — इस व्यक्ति के लॉग इन का यही एकमात्र तरीका है (न पासवर्ड, न कोई दूसरा प्रदाता)। इसे अनलिंक करने पर वे हमेशा के लिए अपने अकाउंट से बाहर हो जाएँगे।',
    'unlinked' => 'प्रदाता :provider अनलिंक किया गया — दोबारा पढ़कर जाँचा: कनेक्शन हट चुका है।',
    'unlink_not_landed' => 'अनलिंक का बदलाव दर्ज नहीं हुआ — कनेक्शन अब भी मौजूद है।',
    'already_verified' => 'पहले से सत्यापित।',
    'marked_verified' => 'सत्यापित मार्क किया गया — डेटाबेस से दोबारा पढ़कर जाँचा गया: :stored UTC।',
    'verify_not_landed' => 'बदलाव दर्ज नहीं हुआ — email_verified_at अब भी खाली है।',
    'already_verified_nothing_to_resend' => 'पहले से सत्यापित — दोबारा भेजने के लिए कुछ नहीं है।',
    'verification_discarded' => 'डिलीवर नहीं हुआ — मेल “:mailer” है, इसलिए सत्यापन ईमेल हटा दिया गया। किसी को कुछ नहीं मिला। उनकी पहचान किसी और तरीके से पक्की करें, फिर “ईमेल को सत्यापित मार्क करें” इस्तेमाल करें।',
    'verification_handed' => 'सत्यापन ईमेल मेलर को सौंप दिया गया (:mailer)।',
    'no_reset_link' => 'रीसेट लिंक नहीं बना: :status',
    'reset_discarded' => 'डिलीवर नहीं हुआ — रीसेट लिंक बना, लेकिन मेल “:mailer” है, इसलिए ईमेल हटा दिया गया। किसी को कुछ नहीं मिला।',
    'reset_handed' => 'पासवर्ड रीसेट ईमेल मेलर को सौंप दिया गया (:mailer)।',
    'verification_not_sent_undeliverable' => 'भेजा नहीं गया — यह पता कभी कोई मेल नहीं पा सकता (:reason), इसलिए सत्यापन ईमेल मेलर को सौंपा नहीं गया। किसी को कुछ नहीं मिला।',
    'reset_not_sent_undeliverable' => 'भेजा नहीं गया — यह पता कभी कोई मेल नहीं पा सकता (:reason), इसलिए रीसेट लिंक मेलर को सौंपा नहीं गया। किसी को कुछ नहीं मिला।',
];
