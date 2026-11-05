<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => '请选择价格计划。',
    'name_required' => '请为季节命名。',
    'name_too_long' => '季节名称不能超过 80 个字符。',
    'choose_first_night' => '请选择第一晚。',
    'choose_last_night' => '请选择最后一晚。',
    'last_before_first' => '最后一晚不能早于第一晚。',
    'price_required' => '请输入每晚价格。',
    'read_only' => '您的组织目前为只读状态——房价更改已暂停。',
    'price_out_of_range' => '请输入大于零且不超过 1,000,000 的每晚价格。',
    'season_saved' => '季节已保存。请核对影响范围，然后应用，以写入这些夜晚的房价。',
    'already_applied' => '此季节已应用，因此无法编辑——它写入的每晚房价已替换原有价格，且系统未记录之前的价格。请改为针对这些夜晚新建一个季节。',
    'season_updated' => '季节已更新。请核对影响范围，然后应用，以写入这些夜晚的房价。',
    'deleted_after_apply' => '季节“:season”已删除。⚠️ 它已应用的每晚房价保持不变——删除季节不会恢复被它替换的价格。如果这些夜晚的价格有误，请自行设置。',
    'deleted_not_applied' => '季节“:season”已删除。它尚未应用，因此房价没有任何变化。',
    'applied' => ':season 已应用——已重写 :units 共 :nights 的房价。',
    'nights_count' => '{1}:count 晚|[0,*]:count 晚',
    'units_count' => '{1}:count 间房|[0,*]:count 间房',
    'overlapping_reapplied' => '{1}:count 个重叠季节已按优先顺序重新应用。|[0,*]:count 个重叠季节已按优先顺序重新应用。',
    'pick_unit_to_continue' => '请选择一个房间以继续。',
    'stay_nights_unpriced' => '{1}此住宿仍有 :count 晚没有价格。|[0,*]此住宿仍有 :count 晚没有价格。',
];
