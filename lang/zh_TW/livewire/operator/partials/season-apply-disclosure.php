<?php

// I18N-01 — zh_TW translation of lang/en/livewire/operator/partials/season-apply-disclosure.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'will_overwrite' => '「:season」將在 :units的 :nights 晚中，覆寫其中 :changing 晚，每晚房價由 :from 改為 :to。',
    'units' => '{1}:count 間房|[0,*]:count 間房',
    'cannot_undo' => '被取代的房價不會儲存在任何地方。<strong>此操作無法撤銷。</strong>',
    'agreed_callout' => '其中 :agreed :carry與 :owners議定的房價（:from 至 :to）。套用會取代這些房價，且不會通知業主。',
    'agreed_carry' => '{1}晚採用|[0,*]晚採用',
    'agreed_owners' => '{1}1 位業主|[0,*]:count 位業主',
    'losing_nights' => '其中 :nights 晚由「:season」決定房價——依規則該季節優先，並會最後寫入。',
    'rewritten_nights' => '套用時，也會重新寫入已套用且重疊的季節中的 :nights 晚。',
    'count_scope' => '業主議定的晚數，僅在透過調價提議議定、且房價仍與議定內容相符時才會計入。業主自行設定的房價，或之後已變動的房價，不計入此處。',
    'booked_keep_price' => '已訂房的住宿維持訂房時的房價——此變更只影響之後提供的房價。',
];
