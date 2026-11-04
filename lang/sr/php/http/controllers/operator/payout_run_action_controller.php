<?php

// I18N-01 — sr translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'Механизам за исплате је искључен (PAYOUT_ENGINE_ENABLED). Укључите га пре покретања групне исплате.',
    'nothing_to_run' => 'Нема шта да се покрене – ниједан власник овде нема обрачун за исплату нити задржану исплату:paused_note.',
    'run_complete' => 'Групна исплата је завршена (режим: :mode) – направљено: :created · исплаћено: :paid · задржано: :held:paused_note.',
    'create_failed' => 'Исплата није направљена – :reason',
    'already_has_payout' => 'Тај обрачун већ има исплату – ништа није направљено.',
    'one_payout_created' => 'Направљена је 1 исплата.',
    'pay_failed' => 'Исплата није могућа – :reason',
    'one_payout_paid' => '1 исплата је означена као исплаћена.',
    'payout_failed_reason' => 'Исплата није успела – :reason',
    'payout_failed_no_reason' => 'Исплата није успела – канал исплате није навео разлог.',
    'create_all_failed' => 'Исплата није направљена – :reason',
    'no_payout_created_paused' => 'Ниједна исплата није направљена:paused_note – наставите исплате на страници власника.',
    'nothing_ready' => 'Ништа није спремно за исплату – ниједан закључени обрачун нема позитиван износ за исплату:paused_note.',
    'no_new_payouts' => 'Нема нових исплата – сваки спреман обрачун већ има исплату:paused_note.',
    'payouts_created' => '{1}:count исплата је направљена:paused_note.|:count исплате су направљене:paused_note.|:count исплата је направљено:paused_note.',
    'some_not_created' => ':message Нека исплата није направљена – :reason',
    'nothing_paid_paused' => 'Ништа није исплаћено:paused_note – наставите исплате на страници власника.',
    'no_pending_payouts' => 'Нема исплата на чекању.',
    'paid_only' => '{1}:count исплата је означена као исплаћена:paused_note.|:count исплате су означене као исплаћене:paused_note.|:count исплата је означено као исплаћено:paused_note.',
    'failed_only' => '{1}:count исплата није успела на каналу исплате:paused_note.|:count исплате нису успеле на каналу исплате:paused_note.|:count исплата није успело на каналу исплате:paused_note.',
    'paid_and_failed' => '{1}:count исплата је означена као исплаћена · неуспело на каналу исплате: :failed:paused_note.|:count исплате су означене као исплаћене · неуспело на каналу исплате: :failed:paused_note.|:count исплата је означено као исплаћено · неуспело на каналу исплате: :failed:paused_note.',
    'some_not_attempted' => ':message Нека исплата није могла да се покрене – :reason',
    'nothing_payable_negative' => 'Нема шта да се исплати – нето износ овог обрачуна је негативан, па се салдо преноси даље и умањује следећи обрачун власника.',
    'nothing_payable_zero' => 'Нема шта да се исплати – нето износ овог обрачуна је нула, па се ништа не дугује.',
    'workspace_paused_until' => 'Организација је паузирана до :date – измене у вези са исплатама су искључене.',
    'workspace_paused_for_now' => 'Организација је паузирана – измене у вези са исплатама су за сада искључене.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1}:count власник са паузираним исплатама прескочен|:count власника са паузираним исплатама прескочена|:count власника са паузираним исплатама прескочено',
];
