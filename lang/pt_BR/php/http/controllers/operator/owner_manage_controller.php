<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Informe a comissão, em porcentagem.',
    'commission_range' => 'A comissão deve ser um número entre 0 e 100.',
    'commission_unchanged' => 'Nada mudou — :rate já é a comissão de :name a partir de :date.',
    'commission_from_today' => 'A comissão de :name é :rate a partir de hoje — não havia contrato em vigor.',
    'commission_waiting_cancelled' => ':name continua com :rate — a alteração prevista para :date não vai acontecer.',
    'commission_from_date' => 'A comissão de :name é :rate a partir de :date — para as reservas calculadas de agora em diante. As reservas já calculadas mantêm a taxa que tinham.',
    'payouts_paused' => 'Repasses em pausa para :name — nenhum repasse será enviado até você retomá-los.',
    'payouts_resumed' => 'Repasses retomados para :name.',
    'period_start_required' => 'Escolha o primeiro dia do período.',
    'period_end_required' => 'Escolha o último dia do período.',
    'period_end_after_start' => 'O período deve terminar no mesmo dia em que começa ou depois.',
    'period_not_closed' => 'Não foi possível fechar este período: :reason Escolha um período que comece depois do último lacrado.',
    'period_sealed' => 'Não é possível gerar de novo um extrato finalizado ou bloqueado.',
    'period_not_closed_other' => 'Não foi possível gerar o extrato desse período.',
    'period_closed' => 'Período fechado — extrato nº :number finalizado com :amount a pagar. A receita ainda não recebida dos hóspedes fica pendente e aparecerá em um extrato futuro.',
    'note_required' => 'Escreva a observação primeiro.',
    'note_max' => 'Uma observação pode ter até 2.000 caracteres.',
    'note_added' => 'Observação adicionada.',
    'remove_password_required' => 'Informe sua senha para remover este proprietário.',
    'password_wrong' => 'Senha incorreta — informe a senha que você usa para entrar.',
    'confirm_name_required' => 'Digite o nome do proprietário para confirmar.',
    'confirm_name_mismatch' => 'O nome digitado não corresponde — o proprietário não foi removido.',
    'owner_removed' => 'Proprietário removido: :name — unidades desvinculadas, todos os extratos e repasses mantidos para a contabilidade.',
    'workspace_paused_until' => 'Organização em pausa — as alterações de proprietários ficam em modo somente leitura até :date.',
    'workspace_paused_for_now' => 'Organização em pausa — as alterações de proprietários ficam em modo somente leitura por enquanto.',
];
