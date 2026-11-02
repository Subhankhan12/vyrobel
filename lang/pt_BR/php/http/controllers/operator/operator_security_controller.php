<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Informe um endereço IP ou bloco CIDR válido (ex.: 203.0.113.4 ou 203.0.113.0/24).',
    'ip_already_listed' => ':ip já está na lista de IPs permitidos.',
    'allowlist_enabled_auto_added' => 'Lista de IPs permitidos ativada. Também adicionamos seu endereço atual (:ip) para que você não fique sem acesso.',
    'ip_added' => ':ip adicionado à lista de IPs permitidos.',
    'remove_would_lock_out' => 'Remover :ip deixaria você sem acesso — é a única regra que permite que seu endereço (:current) acesse o back office. Adicione primeiro uma regra para seu endereço ou remova as outras.',
    'ip_removed_allowlist_off' => ':ip removido — a lista de IPs permitidos está desativada: qualquer pessoa com um login válido pode acessar o back office.',
    'ip_removed' => ':ip removido da lista de IPs permitidos.',
    'session_already_ended' => 'Esta sessão já foi encerrada.',
    'signed_out_of_device' => 'Sessão encerrada em :device.',
    'trusted_device_revoked' => 'Dispositivo confiável revogado.',
    'csv_title' => 'Exportação da conta Vyrobel',
    'csv_name' => 'Nome',
    'csv_email' => 'E-mail',
    'csv_locale' => 'Idioma',
    'csv_roles' => 'Perfis de acesso',
    'csv_activity' => 'Atividade',
    'csv_when' => 'Data',
    'csv_action' => 'Ação',
    'range_this_year' => 'Este ano',
    'range_custom' => 'Período personalizado',
    'range_all_time' => 'Todo o período',
];
