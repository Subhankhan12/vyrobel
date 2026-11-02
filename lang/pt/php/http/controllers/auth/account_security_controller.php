<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'Ainda não é possível ativar a aprovação do início de sessão: a Vyrobel não está a enviar e-mails, por isso a ligação de aprovação nunca lhe chegaria e um novo dispositivo não conseguiria iniciar sessão.',
    'login_approval_on' => 'A aprovação do início de sessão está ativa — um novo dispositivo aguarda até o aprovar.',
    'login_approval_off' => 'A aprovação do início de sessão está desativada.',
    'approval_no_longer_waiting' => 'Esse início de sessão já não está a aguardar — já foi decidido ou expirou.',
    'approval_approved' => 'Aprovado — esse dispositivo já pode concluir o início de sessão.',
    'approval_denied' => 'Recusado — esse início de sessão não pode continuar. Se não reconhece este início de sessão, altere a sua palavra-passe.',
    'login_alerts_on' => 'Os alertas de início de sessão estão ativos — um início de sessão a partir de um novo dispositivo aparece nas suas notificações.',
    'login_alerts_off' => 'Os alertas de início de sessão estão desativados.',
    'session_timeout_off' => 'O tempo limite da sessão está desativado — a sessão mantém-se iniciada durante :duration sem atividade, como antes.',
    'session_timeout_on' => 'A sessão será terminada após :duration sem atividade.',
];
