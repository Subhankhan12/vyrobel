<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'O motor de transferências está desativado (PAYOUT_ENGINE_ENABLED). Ative-o antes de executar uma transferência.',
    'nothing_to_run' => 'Nada a executar — nenhum proprietário aqui tem um extrato a pagar ou uma transferência retida:paused_note.',
    'run_complete' => 'Lote de transferências concluído (modo :mode) — criadas: :created · pagas: :paid · retidas: :held:paused_note.',
    'create_failed' => 'Não foi possível criar a transferência — :reason',
    'already_has_payout' => 'Esse extrato já tem uma transferência — nada foi criado.',
    'one_payout_created' => '1 transferência criada.',
    'pay_failed' => 'Não foi possível pagar — :reason',
    'one_payout_paid' => '1 transferência marcada como paga.',
    'payout_failed_reason' => 'A transferência falhou — :reason',
    'payout_failed_no_reason' => 'A transferência falhou — a via de transferência não indicou o motivo.',
    'create_all_failed' => 'Não foi possível criar as transferências — :reason',
    'no_payout_created_paused' => 'Nenhuma transferência criada:paused_note — retome-as na página do proprietário.',
    'nothing_ready' => 'Nada pronto a pagar — nenhum extrato finalizado tem um valor líquido a receber positivo:paused_note.',
    'no_new_payouts' => 'Nenhuma transferência nova — todos os extratos prontos já têm uma:paused_note.',
    'payouts_created' => '{1} Transferências criadas: :count:paused_note.|[0,*] Transferências criadas: :count:paused_note.',
    'some_not_created' => ':message Não foi possível criar algumas — :reason',
    'nothing_paid_paused' => 'Nada pago:paused_note — retome-as na página do proprietário.',
    'no_pending_payouts' => 'Não há transferências pendentes a pagar.',
    'paid_only' => '{1} Transferências marcadas como pagas: :count:paused_note.|[0,*] Transferências marcadas como pagas: :count:paused_note.',
    'failed_only' => '[0,*] Falhadas na via de transferência: :count:paused_note.',
    'paid_and_failed' => '{1} Transferências marcadas como pagas: :count · falhadas na via de transferência: :failed:paused_note.|[0,*] Transferências marcadas como pagas: :count · falhadas na via de transferência: :failed:paused_note.',
    'some_not_attempted' => ':message Não foi possível tentar pagar algumas — :reason',
    'nothing_payable_negative' => 'Nada a pagar — o líquido deste extrato é negativo, por isso o saldo transita para compensar o próximo extrato do proprietário.',
    'nothing_payable_zero' => 'Nada a pagar — o líquido deste extrato é zero, por isso não há nada em dívida.',
    'workspace_paused_until' => 'Organização em pausa — as transferências estão em modo só de leitura até :date.',
    'workspace_paused_for_now' => 'Organização em pausa — as transferências estão, por agora, em modo só de leitura.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1} proprietário em pausa ignorado: :count|[0,*] proprietários em pausa ignorados: :count',
];
