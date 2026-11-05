<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/unit_rate_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'nothing_to_clear' => '選択した客室には、この期間の客室別料金がありません。客室タイプの料金に戻すものはありません。',
    'choose_unit_to_price' => '料金を設定する客室を1つ以上選択してください。',
    'preview_first' => '適用する前に変更をプレビューしてください。',
    'read_only' => 'ワークスペースは現在読み取り専用のため、料金の変更は一時停止されています。',
    'invalid_dates' => '有効な開始日と終了日を選択してください。',
    'end_before_start' => '終了日を開始日より前にすることはできません。',
    'range_too_long' => '期間が長すぎます。一度に適用できるのは最長1年分です。',
    'choose_mode' => '料金を設定するか、客室別料金を解除して客室タイプの料金に戻すかを選択してください。',
    'invalid_rate' => '有効な1泊料金を入力してください（例：175、175.50）。',
    'choose_room_type' => '客室タイプを選択してください。',
    'no_rate_plan' => 'その客室タイプには料金プランがないため、料金を保存できません。',
    'choose_unit' => '客室を1つ以上選択してください。',
    'too_many_units' => '1回の操作で扱える客室数を超えています。',
    'cleared_summary' => '{1}:count室の客室別料金を解除しました。再び客室タイプの料金に従います。|[0,*]:count室の客室別料金を解除しました。再び客室タイプの料金に従います。',
    'priced_summary' => ':unitsの:nights分に個別の料金を設定しました。',
    'units_count' => '{1}:count室|[0,*]:count室',
    'nights_count' => '{1}:count泊|[0,*]:count泊',
];
