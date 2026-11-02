<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Essa conexão não existe mais.',
    'unlink_refused_only_way_in' => 'Recusado — esta é a única forma de esta pessoa entrar (sem senha, sem outro provedor). Desvincular a deixaria sem acesso para sempre.',
    'unlinked' => 'Provedor :provider desvinculado — relido: a conexão foi removida.',
    'unlink_not_landed' => 'A desvinculação não foi efetivada — a conexão continua lá.',
    'already_verified' => 'Já verificado.',
    'marked_verified' => 'Marcado como verificado — relido no banco de dados: :stored UTC.',
    'verify_not_landed' => 'A gravação não foi efetivada — email_verified_at continua vazio.',
    'already_verified_nothing_to_resend' => 'Já verificado — não há nada para reenviar.',
    'verification_discarded' => 'NÃO ENTREGUE — o e-mail está em “:mailer”, então o e-mail de verificação foi descartado. Ninguém recebeu nada. Confirme a identidade da pessoa de outra forma e depois use “Marcar e-mail como verificado”.',
    'verification_handed' => 'E-mail de verificação entregue ao serviço de e-mail (:mailer).',
    'no_reset_link' => 'Nenhum link de redefinição foi criado: :status',
    'reset_discarded' => 'NÃO ENTREGUE — um link de redefinição foi criado, mas o e-mail está em “:mailer”, então o e-mail foi descartado. Ninguém recebeu nada.',
    'reset_handed' => 'E-mail de redefinição de senha entregue ao serviço de e-mail (:mailer).',
    'verification_not_sent_undeliverable' => 'NÃO ENVIADO — este endereço nunca pode receber e-mails (:reason), então o e-mail de verificação não foi entregue ao serviço de e-mail. Ninguém recebeu nada.',
    'reset_not_sent_undeliverable' => 'NÃO ENVIADO — este endereço nunca pode receber e-mails (:reason), então o link de redefinição não foi entregue ao serviço de e-mail. Ninguém recebeu nada.',
];
