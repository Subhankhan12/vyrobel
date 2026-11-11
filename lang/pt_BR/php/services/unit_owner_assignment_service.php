<?php

// I18N-01 — pt_BR translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Não é possível atribuir uma unidade a :owner — o convite ainda não foi aceito ou o proprietário foi removido. Só um proprietário ativo pode receber uma unidade.',
    'unit_already_owned' => 'A unidade “:unit” já pertence a :owner, por isso não pode ser atribuída a outra pessoa aqui. Só é possível atribuir uma unidade sem proprietário.',
    'unit_owned_by_another' => 'outro proprietário',
    'date_invalid' => 'Escolha uma data válida a partir da qual a unidade será atribuída.',
    'date_in_past' => 'Escolha hoje ou um dia posterior. Um dia que já passou alteraria ganhos que já foram calculados.',
    'date_too_far' => 'Escolha um dia dentro dos próximos :days dias.',
    'no_agreement' => ':owner não tem contrato que cubra a unidade “:unit” em :date. Primeiro crie um contrato para :owner que cubra esta unidade a partir de :date; depois, atribua a unidade.',
    'stay_across_date' => 'Um hóspede tem estadia na unidade “:unit” de :from a :to, período que abrange :date. Escolha :to ou um dia posterior, para que nenhuma estadia seja dividida entre você e :owner.',
    'future_stays_with_another_owner' => 'A unidade “:unit” tem estadias em :date ou depois que já foram calculadas para outro proprietário (:owners). Elas continuam com esse proprietário, por isso a unidade não pode ser atribuída a partir desse dia.',
];
