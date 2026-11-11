<?php

// I18N-01 — pt_BR translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Nenhum registrado',
    'state_switched_on' => 'Ativo — ligado',
    'state_not_active' => 'Inativo',
    'state_configured' => 'Configurado',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hospedagem)',
        'purpose' => 'Executa o aplicativo e o respectivo banco de dados.',
        'data' => 'Tudo o que o produto armazena — todos os registros de contas, hotéis e hóspedes.',
        'state' => 'Ativo — servidor de produção',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Entrega e mantém em cache o site público, na frente do servidor.',
        'data' => 'Todas as requisições que passam pela CDN: endereços IP, URLs, cookies em trânsito.',
        'state' => 'Ativo — observado em todas as respostas',
    ],
    'google' => [
        'name' => 'Google (login)',
        'purpose' => 'Login e cadastro com uma conta Google.',
        'data' => 'A identidade da pessoa no Google: ID da conta, e-mail, nome.',
    ],
    'x' => [
        'name' => 'X (login)',
        'purpose' => 'Login e cadastro com uma conta X.',
        'data' => 'A identidade da pessoa no X: ID da conta, nome de usuário, nome.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Entrega todos os e-mails transacionais, a partir da caixa de e-mail vyrobel.com.',
        'data' => 'Endereços dos destinatários e conteúdo das mensagens — nomes de hóspedes, referências de reserva, datas.',
        'state_active' => 'Ativo — o serviço de e-mail',
        'state_inactive' => 'Inativo — o e-mail está em “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe (pagamentos e faturamento)',
        'purpose' => 'Faturamento de assinaturas, pagamentos de hóspedes com cartão, repasses a proprietários (Connect).',
        'data' => 'Contatos de faturamento, valores, referências de reserva; nos repasses a proprietários, dados de identidade para KYC.',
        'state_fake' => 'Inativo — um cliente simulado está vinculado',
    ],
    'anthropic' => [
        'name' => 'Anthropic (assistente de IA)',
        'purpose' => 'Responde a perguntas no assistente de IA.',
        'data' => 'A pergunta feita e o contexto enviado com ela.',
        'state_off' => 'Inativo — cliente simulado ou sem chave',
    ],
    'channex' => [
        'name' => 'Channex (gerenciador de canais)',
        'purpose' => 'Sincroniza tarifas, disponibilidade e reservas com as OTAs.',
        'data' => 'Dados da reserva e do hóspede trocados com os canais.',
        'state_off' => 'Não conectado',
    ],
];
