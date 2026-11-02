<?php

// I18N-01 — hi translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'पेआउट इंजन बंद है (PAYOUT_ENGINE_ENABLED)। सामूहिक पेआउट चलाने से पहले इसे चालू करें।',
    'nothing_to_run' => 'चलाने के लिए कुछ नहीं — यहाँ किसी मालिक के पास पेआउट के लिए तैयार स्टेटमेंट या रोका गया पेआउट नहीं है:paused_note।',
    'run_complete' => 'सामूहिक पेआउट पूरा (:mode मोड) — बनाए गए: :created · पेआउट किए गए: :paid · रोके गए: :held:paused_note।',
    'create_failed' => 'पेआउट नहीं बनाया जा सका — :reason',
    'already_has_payout' => 'उस स्टेटमेंट का पेआउट पहले से मौजूद है — कुछ नहीं बनाया गया।',
    'one_payout_created' => '1 पेआउट बनाया गया।',
    'pay_failed' => 'पेआउट नहीं किया जा सका — :reason',
    'one_payout_paid' => '1 पेआउट “पेआउट किया गया” के रूप में मार्क किया गया।',
    'payout_failed_reason' => 'पेआउट विफल रहा — :reason',
    'payout_failed_no_reason' => 'पेआउट विफल रहा — पेआउट माध्यम ने कोई कारण नहीं बताया।',
    'create_all_failed' => 'पेआउट नहीं बनाए जा सके — :reason',
    'no_payout_created_paused' => 'कोई पेआउट नहीं बना:paused_note — उन्हें मालिक के पेज पर फिर से शुरू करें।',
    'nothing_ready' => 'पेआउट के लिए कुछ तैयार नहीं — किसी फ़ाइनल स्टेटमेंट की शुद्ध देय राशि शून्य से ज़्यादा नहीं है:paused_note।',
    'no_new_payouts' => 'कोई नया पेआउट नहीं — तैयार हर स्टेटमेंट का पेआउट पहले से मौजूद है:paused_note।',
    'payouts_created' => '{1}:count पेआउट बनाया गया:paused_note।|[2,*]:count पेआउट बनाए गए:paused_note।',
    'some_not_created' => ':message कुछ पेआउट नहीं बनाए जा सके — :reason',
    'nothing_paid_paused' => 'कोई पेआउट नहीं हुआ:paused_note — उन्हें मालिक के पेज पर फिर से शुरू करें।',
    'no_pending_payouts' => 'कोई लंबित पेआउट नहीं है।',
    'paid_only' => '{1}:count पेआउट “पेआउट किया गया” के रूप में मार्क किया गया:paused_note।|[2,*]:count पेआउट “पेआउट किया गया” के रूप में मार्क किए गए:paused_note।',
    'failed_only' => '[0,*]पेआउट माध्यम पर विफल: :count:paused_note।',
    'paid_and_failed' => '{1}:count पेआउट “पेआउट किया गया” के रूप में मार्क किया गया · पेआउट माध्यम पर विफल: :failed:paused_note।|[2,*]:count पेआउट “पेआउट किया गया” के रूप में मार्क किए गए · पेआउट माध्यम पर विफल: :failed:paused_note।',
    'some_not_attempted' => ':message कुछ की कोशिश नहीं की जा सकी — :reason',
    'nothing_payable_negative' => 'कुछ भी देय नहीं — इस स्टेटमेंट की शुद्ध राशि नकारात्मक है, इसलिए यह बैलेंस आगे बढ़ा दिया जाता है और मालिक के अगले स्टेटमेंट में समायोजित होता है।',
    'nothing_payable_zero' => 'कुछ भी देय नहीं — इस स्टेटमेंट की शुद्ध राशि शून्य है, इसलिए कुछ बकाया नहीं है।',
    'workspace_paused_until' => 'वर्कस्पेस स्थगित है — पेआउट :date तक केवल देखने के लिए हैं।',
    'workspace_paused_for_now' => 'वर्कस्पेस स्थगित है — पेआउट अभी केवल देखने के लिए हैं।',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1}:count स्थगित मालिक छोड़ा गया|[2,*]:count स्थगित मालिक छोड़े गए',
];
