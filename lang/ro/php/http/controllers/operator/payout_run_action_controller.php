<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'Motorul de viramente este dezactivat (PAYOUT_ENGINE_ENABLED). Activați-l înainte de a efectua un virament.',
    'nothing_to_run' => 'Nimic de procesat — niciun proprietar de aici nu are un decont de virat sau un virament reținut:paused_note.',
    'run_complete' => 'Ciclu de viramente încheiat (mod: :mode) — create: :created · plătite: :paid · reținute: :held:paused_note.',
    'create_failed' => 'Viramentul nu a putut fi creat — :reason',
    'already_has_payout' => 'Acest decont are deja un virament — nu s-a creat nimic.',
    'one_payout_created' => '1 virament creat.',
    'pay_failed' => 'Viramentul nu a putut fi efectuat — :reason',
    'one_payout_paid' => '1 virament marcat ca plătit.',
    'payout_failed_reason' => 'Viramentul a eșuat — :reason',
    'payout_failed_no_reason' => 'Viramentul a eșuat — canalul de virament nu a indicat niciun motiv.',
    'create_all_failed' => 'Viramentele nu au putut fi create — :reason',
    'no_payout_created_paused' => 'Niciun virament creat:paused_note — reluați viramentele pe pagina proprietarului.',
    'nothing_ready' => 'Nimic gata de virat — niciun decont finalizat nu are un net de virat pozitiv:paused_note.',
    'no_new_payouts' => 'Niciun virament nou — fiecare decont gata de plată are deja unul:paused_note.',
    'payouts_created' => '{1}:count virament creat:paused_note.|:count viramente create:paused_note.|:count de viramente create:paused_note.',
    'some_not_created' => ':message Unele nu au putut fi create — :reason',
    'nothing_paid_paused' => 'Nimic plătit:paused_note — reluați viramentele pe pagina proprietarului.',
    'no_pending_payouts' => 'Niciun virament în așteptare de plătit.',
    'paid_only' => '{1}:count virament marcat ca plătit:paused_note.|:count viramente marcate ca plătite:paused_note.|:count de viramente marcate ca plătite:paused_note.',
    'failed_only' => '{1}:count virament eșuat pe canalul de virament:paused_note.|:count viramente eșuate pe canalul de virament:paused_note.|:count de viramente eșuate pe canalul de virament:paused_note.',
    'paid_and_failed' => '{1}:count virament marcat ca plătit · eșuate pe canalul de virament: :failed:paused_note.|:count viramente marcate ca plătite · eșuate pe canalul de virament: :failed:paused_note.|:count de viramente marcate ca plătite · eșuate pe canalul de virament: :failed:paused_note.',
    'some_not_attempted' => ':message Unele nu au putut fi încercate — :reason',
    'nothing_payable_negative' => 'Nimic de virat — netul acestui decont este negativ, așa că soldul se reportează pentru a compensa următorul decont al proprietarului.',
    'nothing_payable_zero' => 'Nimic de virat — netul acestui decont este zero, deci nu se datorează nimic.',
    'workspace_paused_until' => 'Organizație în pauză — viramentele sunt doar în citire până la :date.',
    'workspace_paused_for_now' => 'Organizație în pauză — viramentele sunt deocamdată doar în citire.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1}omis :count proprietar cu viramentele în pauză|omiși :count proprietari cu viramentele în pauză|omiși :count de proprietari cu viramentele în pauză',
];
