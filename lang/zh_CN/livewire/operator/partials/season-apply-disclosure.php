<?php

// I18N-01 — zh_CN translation of lang/en/livewire/operator/partials/season-apply-disclosure.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'will_overwrite' => ':season 将覆盖 :units 共 :nights 晚中的 :changing 晚，每晚价格从 :from 变为 :to。',
    'units' => '{1}:count 间房|[0,*]:count 间房',
    'cannot_undo' => '被替换的价格不会保存在任何地方。<strong>此操作无法撤销。</strong>',
    'agreed_callout' => '其中 :agreed :carry :owners议定的房价（:from 至 :to）。应用后会替换这些价格，且不会通知业主。',
    'agreed_carry' => '{1}晚采用了|[0,*]晚采用了',
    'agreed_owners' => '{1}1 位业主|[0,*]:count 位业主',
    'losing_nights' => '其中 :nights 晚受 :season 约束，该季节按规则优先，将最后写入。',
    'rewritten_nights' => '应用还会重写已应用的重叠季节中的 :nights 晚。',
    'count_scope' => '该数字仅统计通过调价提议议定的房价，且仅限价格仍与议定价格一致的情况。业主自行设置的房价，或之后已变动的房价，不计入此处。',
    'booked_keep_price' => '已预订的住宿保留预订时的价格——此操作只改变今后提供的价格。',
];
