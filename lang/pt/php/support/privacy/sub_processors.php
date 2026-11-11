<?php

// I18N-01 — pt translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Nenhum registado',
    'state_switched_on' => 'Ativo — ligado',
    'state_not_active' => 'Inativo',
    'state_configured' => 'Configurado',
    'hostinger_hosting' => [
        'name' => 'Hostinger (alojamento web)',
        'purpose' => 'Executa a aplicação e a respetiva base de dados.',
        'data' => 'Tudo o que o produto guarda — todos os registos de contas, de hotéis e de hóspedes.',
        'state' => 'Ativo — o servidor de produção',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Serve e coloca em cache o site público à frente do servidor.',
        'data' => 'Todos os pedidos que passam por ele: endereços IP, URLs e cookies em trânsito.',
        'state' => 'Ativo — observado em todas as respostas',
    ],
    'google' => [
        'name' => 'Google (início de sessão)',
        'purpose' => 'Iniciar sessão e registar-se com uma conta Google.',
        'data' => 'A identidade Google da pessoa: ID da conta, e-mail, nome.',
    ],
    'x' => [
        'name' => 'X (início de sessão)',
        'purpose' => 'Iniciar sessão e registar-se com uma conta X.',
        'data' => 'A identidade X da pessoa: ID da conta, nome de utilizador, nome.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Entrega todos os e-mails transacionais, a partir da caixa de correio vyrobel.com.',
        'data' => 'Endereços dos destinatários e conteúdo das mensagens — nomes de hóspedes, referências de reservas, datas.',
        'state_active' => 'Ativo — o serviço de e-mail',
        'state_inactive' => 'Inativo — o e-mail está em «:mailer»',
    ],
    'stripe' => [
        'name' => 'Stripe (pagamentos e faturação)',
        'purpose' => 'Faturação de subscrições, pagamentos com cartão dos hóspedes, transferências para proprietários (Connect).',
        'data' => 'Contactos de faturação, valores, referências de reservas; nas transferências para proprietários, a identidade para o KYC.',
        'state_fake' => 'Inativo — está associado um cliente simulado',
    ],
    'anthropic' => [
        'name' => 'Anthropic (assistente de IA)',
        'purpose' => 'Responde às perguntas feitas no assistente de IA.',
        'data' => 'A pergunta feita e o contexto enviado com ela.',
        'state_off' => 'Inativo — cliente simulado ou sem chave',
    ],
    'channex' => [
        'name' => 'Channex (gestor de canais)',
        'purpose' => 'Sincroniza tarifas, disponibilidade e reservas com as OTA.',
        'data' => 'Dados de reservas e de hóspedes trocados com os canais.',
        'state_off' => 'Não ligado',
    ],
];
