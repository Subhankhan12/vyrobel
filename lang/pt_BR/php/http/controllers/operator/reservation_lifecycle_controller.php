<?php

// I18N-01 — pt_BR translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Só uma retenção provisória pode ser confirmada — esta reserva está com status “:status”.',
    'hold_confirmed' => 'Retenção confirmada — a reserva agora é uma estadia confirmada.',
    'cannot_cancel_closed' => 'Essa reserva não pode ser cancelada — ela já está encerrada.',
    'reason_too_long' => 'O motivo do cancelamento é longo demais — máximo de 255 caracteres.',
    'cancel_rejected' => 'Não foi possível cancelar essa reserva — o serviço de reservas recusou o cancelamento. Nada foi alterado.',
    'cancelled_refunded' => 'Reserva cancelada e reembolso de :amount feito (dentro do prazo de cancelamento gratuito). As noites que ela ocupava voltaram ao calendário.',
    'cancelled' => 'Reserva cancelada. As noites que ela ocupava voltaram ao calendário.',
    'cannot_no_show' => 'Essa reserva não pode ser marcada como no-show — só uma reserva confirmada pode.',
    'no_show_rejected' => 'Não foi possível marcar essa reserva como no-show — o serviço de reservas recusou a alteração. Nada foi alterado.',
    'marked_no_show' => 'Marcada como no-show. As noites que ela ocupava voltaram ao calendário.',
];
