<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => 'Só um convite pendente pode ser reemitido.',
    'last_owner_role' => 'Este é o último “Administrador principal” ativo. Torne outra pessoa “Administrador principal” primeiro.',
    'role_changed' => ':name agora tem o perfil :role.',
    'cannot_revoke_self' => 'Você não pode revogar seu próprio acesso. Peça a outro “Administrador principal”.',
    'last_owner_revoke' => 'Este é o último “Administrador principal” ativo, e o acesso dele não pode ser revogado.',
    'access_revoked' => '{1} Acesso de :name revogado; :count sessão encerrada.|[0,*] Acesso de :name revogado; :count sessões encerradas.',
    'only_revoked_restored' => 'Só um administrador com acesso revogado pode ter o acesso restabelecido.',
    'access_restored' => 'Acesso de :name restabelecido.',
    'ended_own_other_sessions' => '{1} :count outra sessão sua foi encerrada — esta continua conectada.|[0,*] :count outras sessões suas foram encerradas — esta continua conectada.',
    'ended_sessions_for' => '{1} :count sessão de :name encerrada.|[0,*] :count sessões de :name encerradas.',
];
