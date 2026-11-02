<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Introduza a comissão, em percentagem.',
    'commission_range' => 'A comissão tem de ser um número entre 0 e 100.',
    'commission_unchanged' => 'Nada foi alterado — :rate já é a comissão de :name a partir de :date.',
    'commission_from_today' => 'A comissão de :name é de :rate a partir de hoje — não havia nenhum contrato em vigor.',
    'commission_waiting_cancelled' => ':name mantém :rate — a alteração prevista para :date não vai acontecer.',
    'commission_from_date' => 'A comissão de :name é de :rate a partir de :date — para as reservas calculadas daqui em diante. As reservas já calculadas mantêm a comissão que tinham.',
    'payouts_paused' => 'Transferências em pausa para :name — nenhuma transferência é enviada até as retomar.',
    'payouts_resumed' => 'Transferências retomadas para :name.',
    'period_start_required' => 'Escolha o primeiro dia do período.',
    'period_end_required' => 'Escolha o último dia do período.',
    'period_end_after_start' => 'O período tem de terminar no dia em que começa ou depois.',
    'period_not_closed' => 'Não foi possível fechar esse período: :reason Escolha um período que comece depois do último período fechado.',
    'period_closed' => 'Período fechado — extrato n.º :number finalizado com :amount a pagar. A receita ainda não cobrada aos hóspedes fica pendente e aparecerá num extrato posterior.',
    'note_required' => 'Escreva a nota primeiro.',
    'note_max' => 'Uma nota pode ter até 2000 caracteres.',
    'note_added' => 'Nota adicionada.',
    'remove_password_required' => 'Introduza a sua palavra-passe para remover este proprietário.',
    'password_wrong' => 'Essa palavra-passe não está correta — introduza a que usa para iniciar sessão.',
    'confirm_name_required' => 'Escreva o nome do proprietário para confirmar.',
    'confirm_name_mismatch' => 'O nome que escreveu não corresponde — o proprietário não foi removido.',
    'owner_removed' => ':name removido — unidades desassociadas, todos os extratos e transferências mantidos para a contabilidade.',
    'workspace_paused_until' => 'Organização em pausa — as alterações a proprietários estão em modo só de leitura até :date.',
    'workspace_paused_for_now' => 'Organização em pausa — as alterações a proprietários estão, por agora, em modo só de leitura.',
];
