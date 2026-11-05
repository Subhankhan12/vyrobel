<?php

// I18N-01 — ja translation of lang/en/pdfs/operator-schedule.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'eyebrow' => 'チームのシフト表',
    'week_of' => ':weekの週',
    'doc_title' => '週間シフト表',
    'doc_sub' => ':property · :weekの週 · 作成：:generated',
    'published' => '公開：:date',
    'published_by' => '公開：:date（:name）',
    'not_published' => '未公開です。この週はまだチームに送信されていません。',
    'team' => 'チーム',
    'shifts' => 'シフト数',
    'hours' => '時間数',
    'open' => '担当者未定',
    'conflicts' => '重複',
    'no_staff' => '選択した週のシフト表にスタッフがいません。',
    'team_member' => 'チームメンバー',
    'cell_conflict' => ':shift / 休暇！',
    'cell_leave' => '休暇',
    'legend' => '<strong>シフト表の見方：</strong>セルにはシフト名と時間を表示 · <strong>休暇</strong> = 承認済みの休暇 · <strong>—</strong> = シフトなし · <strong>「/ 休暇！」</strong> = 承認済みの休暇日に入っているシフト。すべての状態を文字で記載しているため、白黒で印刷しても正しく読めます。',
    'shift_hours' => ':early：:early_hours · :day：:day_hours · :late：:late_hours · シフト時間は各セルに表示されます。',
    'conflict_count' => '{1}<strong style="color:#B0322A">重複:count件</strong>：承認済みの休暇日に入っているシフトです。|[0,*]<strong style="color:#B0322A">重複:count件</strong>：承認済みの休暇日に入っているシフトです。',
    'open_shifts' => '担当者未定のシフト',
    'all_assigned' => 'このシフト表のシフトはすべて担当者が決まっています。今週、担当者未定の枠はありません。',
    'any_department' => '部署指定なし',
    'time_off' => '休暇',
    'foot_generated' => ':property · 作成：:generated',
    'foot_week' => '週間スケジュール · :week',
    'snapshot_note' => 'このシフト表は、:generated時点のシフトを記録したものです。その後の変更はこの印刷物には反映されません。シフト交換の申請は、Vyrobelアプリまたはフロントで行ってください。',
];
