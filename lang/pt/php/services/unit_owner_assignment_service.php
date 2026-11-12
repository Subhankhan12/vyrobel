<?php

// I18N-01 — pt translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Não é possível atribuir uma unidade a :owner — o convite ainda não foi aceite, ou o proprietário foi removido. Só é possível atribuir uma unidade a um proprietário ativo.',
    'unit_on_pending_invitation' => 'A unidade «:unit» consta de um convite de proprietário ainda por aceitar. Passa a ser desse proprietário logo que o convite seja aceite — ou cancele primeiro o convite para atribuir a unidade a outra pessoa.',
    'unit_already_owned' => 'A unidade «:unit» já pertence a :owner, por isso não pode ser atribuída a outra pessoa aqui. Só é possível atribuir uma unidade sem proprietário.',
    'unit_owned_by_another' => 'outro proprietário',
    'date_invalid' => 'Escolha uma data válida a partir da qual atribuir a unidade.',
    'date_in_past' => 'Escolha hoje ou um dia posterior. Um dia que já passou alteraria rendimentos que já estão calculados.',
    'date_too_far' => 'Escolha um dia dentro dos próximos :days dias.',
    'no_agreement' => ':owner não tem nenhum contrato que abranja «:unit» em :date. Crie primeiro um contrato para :owner que abranja esta unidade a partir de :date e só depois atribua-lhe a unidade.',
    'stay_across_date' => 'Um hóspede tem uma estadia na unidade «:unit» de :from a :to, que atravessa :date. Escolha :to ou um dia posterior, para que nenhuma estadia fique dividida entre si e :owner.',
    'future_stays_with_another_owner' => 'A unidade «:unit» tem estadias a partir de :date que já estão calculadas para outro proprietário (:owners). Essas estadias ficam com o respetivo proprietário, por isso não é possível atribuir a unidade a partir desse dia.',
];
