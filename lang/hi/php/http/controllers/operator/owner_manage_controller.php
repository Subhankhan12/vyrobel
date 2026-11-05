<?php

// I18N-01 — hi translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'कमीशन प्रतिशत में दर्ज करें।',
    'commission_range' => 'कमीशन 0 से 100 के बीच की संख्या होनी चाहिए।',
    'commission_unchanged' => 'कुछ नहीं बदला — :date से :name का कमीशन पहले से :rate है।',
    'commission_from_today' => 'आज से :name का कमीशन :rate है — पहले से कोई अनुबंध लागू नहीं था।',
    'commission_waiting_cancelled' => ':name का कमीशन :rate ही रहेगा — :date के लिए तय बदलाव अब लागू नहीं होगा।',
    'commission_from_date' => ':date से :name का कमीशन :rate है — अब से जिन बुकिंग का हिसाब लगाया जाएगा, उन पर लागू। जिन बुकिंग का हिसाब पहले ही लग चुका है, उनकी दर वही रहेगी।',
    'payouts_paused' => ':name के लिए पेआउट स्थगित किए गए — आपके फिर से शुरू करने तक कोई पेआउट नहीं भेजा जाएगा।',
    'payouts_resumed' => ':name के लिए पेआउट फिर से शुरू किए गए।',
    'period_start_required' => 'अवधि का पहला दिन चुनें।',
    'period_end_required' => 'अवधि का आखिरी दिन चुनें।',
    'period_end_after_start' => 'अवधि का आखिरी दिन उसके शुरू होने के दिन या उसके बाद का होना चाहिए।',
    'period_not_closed' => 'उस अवधि को बंद नहीं किया जा सका: :reason ऐसी अवधि चुनें जो पिछली सील की गई अवधि के बाद शुरू हो।',
    'period_sealed' => 'फ़ाइनल या सील किए गए स्टेटमेंट को दोबारा नहीं बनाया जा सकता।',
    'period_not_closed_other' => 'उस अवधि का स्टेटमेंट नहीं बनाया जा सका।',
    'period_closed' => 'अवधि बंद की गई — स्टेटमेंट #:number :amount देय राशि के साथ फ़ाइनल किया गया। मेहमानों से जो राजस्व अभी तक वसूल नहीं हुआ है, वह किसी स्टेटमेंट में शामिल नहीं होता और बाद के स्टेटमेंट में दिखाई देगा।',
    'note_required' => 'पहले नोट लिखें।',
    'note_max' => 'नोट अधिकतम 2,000 अक्षरों का हो सकता है।',
    'note_added' => 'नोट जोड़ा गया।',
    'remove_password_required' => 'इस मालिक को संग्रहीत करने के लिए अपना पासवर्ड दर्ज करें।',
    'password_wrong' => 'यह पासवर्ड सही नहीं है — वही पासवर्ड दर्ज करें जिससे आप लॉग इन करते हैं।',
    'confirm_name_required' => 'पुष्टि के लिए मालिक का नाम टाइप करें।',
    'confirm_name_mismatch' => 'आपका टाइप किया नाम मेल नहीं खाता — मालिक को संग्रहीत नहीं किया गया।',
    'owner_removed' => ':name संग्रहीत किया गया — कमरे मालिक से अलग किए गए, सभी स्टेटमेंट और पेआउट हिसाब-किताब के लिए रखे गए हैं।',
    'workspace_paused_until' => 'वर्कस्पेस स्थगित है — :date तक मालिकों से जुड़े बदलाव केवल देखने के लिए हैं।',
    'workspace_paused_for_now' => 'वर्कस्पेस स्थगित है — अभी मालिकों से जुड़े बदलाव केवल देखने के लिए हैं।',
];
