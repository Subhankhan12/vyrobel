<?php

// I18N-01 — pt_BR translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'A estadia volta para check-in feito.',
    'pending_tasks_removed' => '{1} :count tarefa de limpeza de saída será removida — ainda não foi iniciada.|[0,*] :count tarefas de limpeza de saída serão removidas — ainda não foram iniciadas.',
    'started_tasks_kept' => '{1} :count tarefa de limpeza já foi iniciada e não será alterada — cancelar um trabalho que alguém já começou é uma decisão da governança, não uma simples ação de desfazer.|[0,*] :count tarefas de limpeza já foram iniciadas e não serão alteradas — cancelar um trabalho que alguém já começou é uma decisão da governança, não uma simples ação de desfazer.',
    'unit_stays_dirty' => 'A unidade continua marcada como suja. O hóspede voltou ao quarto, então “limpa” pode não ser verdade.',
    'notifications_stay_delivered' => 'As notificações já entregues à equipe e ao hóspede continuam entregues.',
    'review_unknown' => 'Não há registro indicando se um pedido de avaliação foi enviado para esta estadia, porque o check-out aconteceu antes de este produto começar a registrar esses pedidos. Ele pode ou não ter sido enviado.',
    'review_sent_on' => 'Um pedido de avaliação foi enviado por e-mail ao hóspede em :date. Não é possível desfazer o envio.',
    'review_sent' => 'Um pedido de avaliação foi enviado por e-mail ao hóspede. Não é possível desfazer o envio.',
    'review_suppressed_by_operator' => 'Nenhum pedido de avaliação foi enviado — esta propriedade está com o e-mail de pedido de avaliação desativado.',
    'review_suppressed_by_guest' => 'Nenhum pedido de avaliação foi enviado — o hóspede pediu para não receber e-mails de avaliação.',
    'review_not_sent' => 'Nenhum pedido de avaliação foi enviado — não havia endereço de contato, ou o envio falhou.',
    'refused_not_checked_out' => 'Só uma estadia com check-out feito pode ter o check-out revertido; o status desta é “:status”.',
];
