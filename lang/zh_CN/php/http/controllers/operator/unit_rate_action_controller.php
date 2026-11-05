<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => '这些房间在该日期范围内都没有单独定价——没有可清除的价格。',
    'choose_unit_to_price' => '请至少选择一个要定价的房间。',
    'preview_first' => '请先预览更改，再应用。',
    'read_only' => '您的组织目前为只读状态——房价更改已暂停。',
    'invalid_dates' => '请选择有效的开始日期和结束日期。',
    'end_before_start' => '结束日期不能早于开始日期。',
    'range_too_long' => '日期范围过长——每次最多应用一年。',
    'choose_mode' => '请选择是设置价格，还是清除单独定价。',
    'invalid_rate' => '请输入有效的每晚房价，例如：175 或 175.50。',
    'choose_room_type' => '请选择房型。',
    'no_rate_plan' => '该房型没有价格计划，因此价格无处保存。',
    'choose_unit' => '请至少选择一个房间。',
    'too_many_units' => '单次操作的房间数量过多。',
    'cleared_summary' => '{1}已清除 :count 间房的单独定价——该房间将重新沿用房型房价。|[0,*]已清除 :count 间房的单独定价——这些房间将重新沿用房型房价。',
    'priced_summary' => '已为 :units 设置 :nights 的单独定价。',
    'units_count' => '{1}:count 间房|[0,*]:count 间房',
    'nights_count' => '{1}:count 晚|[0,*]:count 晚',
];
