<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'A aprovação de login ainda não pode ser ativada: a Vyrobel não está enviando e-mails, então o link de aprovação nunca chegaria até você e um novo dispositivo não conseguiria entrar.',
    'login_approval_on' => 'A aprovação de login está ativada — um novo dispositivo aguarda até você aprová-lo.',
    'login_approval_off' => 'A aprovação de login está desativada.',
    'approval_no_longer_waiting' => 'Esse login não está mais aguardando — já foi decidido ou expirou.',
    'approval_approved' => 'Aprovado — esse dispositivo já pode concluir o login.',
    'approval_denied' => 'Recusado — esse login não pode continuar. Se não foi você, altere sua senha.',
    'login_alerts_on' => 'Os alertas de login estão ativados — um login de um novo dispositivo aparece nas suas notificações.',
    'login_alerts_off' => 'Os alertas de login estão desativados.',
    'session_timeout_off' => 'O tempo limite da sessão está desativado — sua sessão continua aberta por :duration sem atividade, como antes.',
    'session_timeout_on' => 'Sua sessão será encerrada após :duration sem atividade.',
];
