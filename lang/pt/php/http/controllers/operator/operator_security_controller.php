<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Introduza um endereço IP ou bloco CIDR válido (p. ex. 203.0.113.4 ou 203.0.113.0/24).',
    'ip_already_listed' => ':ip já está na lista de IP permitidos.',
    'allowlist_enabled_auto_added' => 'Lista de IP permitidos ativada. Também adicionámos o seu endereço atual (:ip) para não bloquear o seu próprio acesso.',
    'ip_added' => ':ip adicionado à lista de IP permitidos.',
    'remove_would_lock_out' => 'Remover :ip bloquearia o seu acesso — é a única regra que permite ao seu endereço (:current) aceder ao back office. Adicione primeiro uma regra para o seu endereço ou remova as outras.',
    'ip_removed_allowlist_off' => ':ip removido — a lista de IP permitidos está desativada: qualquer pessoa com um início de sessão válido pode aceder ao back office.',
    'ip_removed' => ':ip removido da lista de IP permitidos.',
    'session_already_ended' => 'Essa sessão já terminou.',
    'signed_out_of_device' => 'Sessão terminada em :device.',
    'trusted_device_revoked' => 'Dispositivo de confiança revogado.',
    'csv_title' => 'Exportação da conta Vyrobel',
    'csv_name' => 'Nome',
    'csv_email' => 'E-mail',
    'csv_locale' => 'Idioma',
    'csv_roles' => 'Funções',
    'csv_activity' => 'Atividade',
    'csv_when' => 'Data',
    'csv_action' => 'Ação',
    'range_this_year' => 'Este ano',
    'range_custom' => 'Intervalo personalizado',
    'range_all_time' => 'Desde sempre',
];
