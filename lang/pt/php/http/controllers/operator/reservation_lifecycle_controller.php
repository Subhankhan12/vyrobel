<?php

// I18N-01 — pt translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Só é possível confirmar uma retenção provisória — estado desta reserva: :status.',
    'hold_confirmed' => 'Retenção confirmada — a reserva passa a ser uma estadia confirmada.',
    'cannot_cancel_closed' => 'Não é possível cancelar essa reserva — já está fechada.',
    'reason_too_long' => 'Esse motivo de cancelamento é demasiado longo — máximo de 255 caracteres.',
    'cancel_rejected' => 'Não foi possível cancelar essa reserva — o serviço de reservas recusou o pedido. Nada foi alterado.',
    'cancelled_refunded' => 'Reserva cancelada e reembolso de :amount efetuado (dentro do período de cancelamento gratuito). As noites que a reserva ocupava voltam a ficar livres no calendário.',
    'cancelled' => 'Reserva cancelada. As noites que a reserva ocupava voltam a ficar livres no calendário.',
    'cannot_no_show' => 'Não é possível marcar essa reserva como no-show — só uma reserva confirmada o pode ser.',
    'no_show_rejected' => 'Não foi possível marcar essa reserva como no-show — o serviço de reservas recusou o pedido. Nada foi alterado.',
    'marked_no_show' => 'Marcada como no-show. As noites que a reserva ocupava voltam a ficar livres no calendário.',
];
