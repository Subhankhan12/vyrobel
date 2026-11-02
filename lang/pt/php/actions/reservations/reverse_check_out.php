<?php

// I18N-01 — pt translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'A estadia volta ao estado Check-in feito.',
    'pending_tasks_removed' => '{1} :count tarefa de limpeza de partida será removida — ainda não foi iniciada.|[0,*] :count tarefas de limpeza de partida serão removidas — ainda não foram iniciadas.',
    'started_tasks_kept' => '{1} :count tarefa de limpeza já foi iniciada e não será alterada — cancelar trabalho que alguém já começou é uma decisão que cabe à Limpeza, não a uma anulação.|[0,*] :count tarefas de limpeza já foram iniciadas e não serão alteradas — cancelar trabalho que alguém já começou é uma decisão que cabe à Limpeza, não a uma anulação.',
    'unit_stays_dirty' => 'A unidade continua marcada como suja. O hóspede voltou ao quarto, por isso «limpa» pode não ser verdade.',
    'notifications_stay_delivered' => 'As notificações já entregues à equipa e ao hóspede mantêm-se entregues.',
    'review_unknown' => 'Não sabemos se foi enviado um pedido de avaliação para esta estadia, porque o check-out foi feito antes de este produto começar a registar esses pedidos. Pode ou não ter sido enviado.',
    'review_sent_on' => 'Foi enviado ao hóspede um pedido de avaliação por e-mail (data: :date). Não é possível anular o envio.',
    'review_sent' => 'Foi enviado ao hóspede um pedido de avaliação por e-mail. Não é possível anular o envio.',
    'review_suppressed_by_operator' => 'Não foi enviado nenhum pedido de avaliação — este alojamento tem o e-mail de pedido de avaliação desativado.',
    'review_suppressed_by_guest' => 'Não foi enviado nenhum pedido de avaliação — o hóspede pediu para não receber e-mails de avaliação.',
    'review_not_sent' => 'Não foi enviado nenhum pedido de avaliação — não havia endereço de contacto, ou o envio falhou.',
    'refused_not_checked_out' => 'Só é possível reverter o check-out de uma estadia com check-out feito; esta estadia está no estado :status.',
];
