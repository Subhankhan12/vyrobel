<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Essa associação já não existe.',
    'unlink_refused_only_way_in' => 'Recusado — esta é a única forma de esta pessoa iniciar sessão (sem palavra-passe, sem outro fornecedor). Desassociá-la deixaria a pessoa sem acesso para sempre.',
    'unlinked' => 'Fornecedor :provider desassociado — relido: a associação já não existe.',
    'unlink_not_landed' => 'A desassociação não se concretizou — a associação continua a existir.',
    'already_verified' => 'Já confirmado.',
    'marked_verified' => 'Marcado como confirmado — relido na base de dados: :stored UTC.',
    'verify_not_landed' => 'A gravação não se concretizou — email_verified_at continua vazio.',
    'already_verified_nothing_to_resend' => 'Já confirmado — nada para reenviar.',
    'verification_discarded' => 'NÃO ENTREGUE — o e-mail está em «:mailer», por isso o e-mail de confirmação foi descartado. Ninguém recebeu nada. Confirme a identidade da pessoa de outra forma e depois use «Marcar e-mail como confirmado».',
    'verification_handed' => 'E-mail de confirmação entregue ao serviço de e-mail (:mailer).',
    'no_reset_link' => 'Não foi criada nenhuma ligação de reposição: :status',
    'reset_discarded' => 'NÃO ENTREGUE — foi criada uma ligação de reposição, mas o e-mail está em «:mailer», por isso o e-mail foi descartado. Ninguém recebeu nada.',
    'reset_handed' => 'E-mail de reposição da palavra-passe entregue ao serviço de e-mail (:mailer).',
    'verification_not_sent_undeliverable' => 'NÃO ENVIADO — este endereço nunca pode receber e-mails (:reason), por isso o e-mail de confirmação não foi entregue ao serviço de e-mail. Ninguém recebeu nada.',
    'reset_not_sent_undeliverable' => 'NÃO ENVIADO — este endereço nunca pode receber e-mails (:reason), por isso a ligação de reposição não foi entregue ao serviço de e-mail. Ninguém recebeu nada.',
];
