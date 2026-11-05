<?php

// I18N-01 — ja translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '予約は確定済みに戻り、客室は同じ宿泊日で確保されたままになります。',
    'history_kept' => 'チェックインの記録自体は予約の履歴に残ります。チェックインは実際に行われ、記録にもそのとおり残ります。元に戻した操作は、その記録に並べて追加されます。',
    'charges_kept' => '{1}チェックイン後に計上された請求項目:count件（合計:amount）は、フォリオに残ります。チェックインを元に戻しても、請求は取り消されません。これらも誤りだった場合は、フォリオで削除してください。|[0,*]チェックイン後に計上された請求項目:count件（合計:amount）は、フォリオに残ります。チェックインを元に戻しても、請求は取り消されません。これらも誤りだった場合は、フォリオで削除してください。',
    'online_check_in_kept' => 'ゲスト自身によるオンラインチェックインは完了済みのままです。ゲストは実際に入力を済ませているため、再入力を求めることはありません。',
    'already_reversed' => 'このチェックインは少し前にすでに元に戻されています。予約は確定済みに戻っています。',
    'refused_not_checked_in' => 'チェックインを元に戻せるのは、チェックイン済みの予約のみです。この予約のステータスは「:status」です。',
];
