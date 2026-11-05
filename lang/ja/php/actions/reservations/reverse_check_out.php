<?php

// I18N-01 — ja translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '予約はチェックイン済みに戻ります。',
    'pending_tasks_removed' => '{1}未着手のチェックアウト後の清掃タスク:count件が削除されます。|[0,*]未着手のチェックアウト後の清掃タスク:count件が削除されます。',
    'started_tasks_kept' => '{1}すでに開始された清掃タスク:count件はそのまま残ります。誰かが始めた作業を中止するかどうかはハウスキーピングが判断することで、元に戻す操作の対象ではありません。|[0,*]すでに開始された清掃タスク:count件はそのまま残ります。誰かが始めた作業を中止するかどうかはハウスキーピングが判断することで、元に戻す操作の対象ではありません。',
    'unit_stays_dirty' => '客室は未清掃のままです。ゲストが客室に戻っているため、「清掃済み」が正しいとは限りません。',
    'notifications_stay_delivered' => 'スタッフとゲストにすでに配信された通知は、配信済みのままです。',
    'review_unknown' => 'この宿泊についてクチコミ依頼が送信されたかどうかの記録はありません。本システムがクチコミ依頼の記録を始める前にチェックアウトした予約のためです。送信された可能性も、送信されていない可能性もあります。',
    'review_sent_on' => ':dateにクチコミ依頼をゲストへメールで送信済みです。送信を撤回することはできません。',
    'review_sent' => 'クチコミ依頼をゲストへメールで送信済みです。送信を撤回することはできません。',
    'review_suppressed_by_operator' => 'クチコミ依頼は送信されていません。この施設ではクチコミ依頼メールがオフになっています。',
    'review_suppressed_by_guest' => 'クチコミ依頼は送信されていません。ゲストがクチコミ依頼メールを受け取らないよう希望しています。',
    'review_not_sent' => 'クチコミ依頼は送信されていません。連絡先のメールアドレスがなかったか、送信に失敗しました。',
    'refused_not_checked_out' => 'チェックアウトを元に戻せるのは、チェックアウト済みの予約のみです。この予約のステータスは「:status」です。',
];
