<?php

// I18N-01 — zh_CN translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => '不能给 :owner 分配房间——对方尚未接受邀请，或已被归档。只能给已启用的业主分配房间。',
    'unit_on_pending_invitation' => '“:unit”已列入一份尚未接受的业主邀请。对方接受邀请后，此房间即归其所有——也可先取消该邀请，再将房间分配给其他人。',
    'unit_already_owned' => '“:unit”已有业主（:owner），因此无法在此分配给其他人。只能分配尚无业主的房间。',
    'unit_owned_by_another' => '另一位业主',
    'date_invalid' => '请选择一个有效日期，作为分配房间的起始日期。',
    'date_in_past' => '请选择今天或之后的日期。已过去的日期会改变已经计算好的收入。',
    'date_too_far' => '请选择未来 :days 天内的日期。',
    'no_agreement' => ':owner 在 :date 没有涵盖“:unit”的托管协议。请先为 :owner 建立一份自 :date 起涵盖此房间的托管协议，然后再分配该房间。',
    'stay_across_date' => '有客人入住“:unit”，时间为 :from 至 :to，跨越了 :date。请选择 :to 或之后的日期，这样就不会有住宿由您和 :owner 共有。',
    'future_stays_with_another_owner' => '“:unit”在 :date 当天及之后有住宿，已为其他业主（:owners）计算过收入。这些住宿归其他业主，因此无法自该日起分配此房间。',
];
