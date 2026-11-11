<?php

// I18N-01 — ja translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':ownerには客室を割り当てられません。まだ招待を承諾していないか、すでにアーカイブされています。客室を割り当てできるのは、有効なオーナーのみです。',
    'unit_already_owned' => '「:unit」はすでに:ownerに割り当てられているため、ここで別のオーナーに割り当てることはできません。割り当てできるのは、オーナーがいない客室のみです。',
    'unit_owned_by_another' => '別のオーナー',
    'date_invalid' => '客室を割り当てる開始日として、有効な日付を選択してください。',
    'date_in_past' => '本日またはそれ以降の日付を選択してください。過ぎた日付を選ぶと、すでに計算済みの収益が変わってしまいます。',
    'date_too_far' => '今後:days日以内の日付を選択してください。',
    'no_agreement' => ':ownerには、:dateに「:unit」を対象とする管理委託契約がありません。先に、:dateからこの客室を対象とする:ownerの管理委託契約を作成してから、客室を割り当ててください。',
    'stay_across_date' => '「:unit」に、:fromから:toまでのゲストの宿泊があり、:dateをまたいでいます。1件の宿泊が自社と:ownerで分かれないよう、:to以降の日付を選択してください。',
    'future_stays_with_another_owner' => '「:unit」には、:date以降の宿泊のうち、すでに別のオーナー（:owners）の収益として計算されているものがあります。それらの宿泊は引き続きそのオーナーの扱いになるため、その日から客室を割り当てることはできません。',
];
