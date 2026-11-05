<?php

// I18N-01 — zh_CN translation of lang/en/pdfs/operator-schedule.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'eyebrow' => '团队排班',
    'week_of' => ':week 当周',
    'doc_title' => '每周排班表',
    'doc_sub' => ':property · :week 当周 · 生成于 :generated',
    'published' => '发布于 :date',
    'published_by' => ':name 发布于 :date',
    'not_published' => '尚未发布——本周排班尚未发送给团队。',
    'team' => '团队',
    'shifts' => '班次',
    'hours' => '工时',
    'open' => '待认领',
    'conflicts' => '冲突',
    'no_staff' => '所选周的排班表上没有员工。',
    'team_member' => '团队成员',
    'cell_conflict' => ':shift / 请假！',
    'cell_leave' => '请假',
    'legend' => '<strong>如何阅读本排班表：</strong>单元格显示班次名称及其时间 · <strong>请假</strong> = 已批准的请假 · <strong>—</strong> = 未排班 · <strong>“/ 请假！”</strong> = 在已批准请假日排的班次。每种状态都以文字标明，因此黑白打印也能正确显示。',
    'shift_hours' => ':early :early_hours · :day :day_hours · :late :late_hours · 每个单元格显示班次时间。',
    'conflict_count' => '{1}<strong style="color:#B0322A">:count 处冲突</strong>——在已批准请假日排了班次。|[0,*]<strong style="color:#B0322A">:count 处冲突</strong>——在已批准请假日排了班次。',
    'open_shifts' => '待认领班次',
    'all_assigned' => '本排班表上的所有班次均已分配——本周没有待认领班次。',
    'any_department' => '不限部门',
    'time_off' => '请假',
    'foot_generated' => ':property · 生成于 :generated',
    'foot_week' => '周排班 · :week',
    'snapshot_note' => '本排班表是截至 :generated 的排班快照——之后的任何更改都不会出现在这份打印件上。换班申请请通过 Vyrobel 应用或前台提交。',
];
