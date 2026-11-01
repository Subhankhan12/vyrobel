<?php

// I18N-01 — es translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Solo se puede confirmar un bloqueo provisional: esta reserva tiene el estado “:status”.',
    'hold_confirmed' => 'Bloqueo confirmado: la reserva es ahora una estancia confirmada.',
    'cannot_cancel_closed' => 'Esa reserva no se puede cancelar: ya está cerrada.',
    'reason_too_long' => 'Ese motivo de cancelación es demasiado largo: 255 caracteres como máximo.',
    'cancel_rejected' => 'No se pudo cancelar esa reserva: el servicio de reservas la rechazó. No se cambió nada.',
    'cancelled_refunded' => 'Se canceló la reserva y se reembolsó :amount (dentro del plazo de cancelación gratuita). Las noches que ocupaba vuelven a estar libres en el calendario.',
    'cancelled' => 'Se canceló la reserva. Las noches que ocupaba vuelven a estar libres en el calendario.',
    'cannot_no_show' => 'Esa reserva no se puede marcar como no show: solo se puede hacer con una reserva confirmada.',
    'no_show_rejected' => 'No se pudo marcar esa reserva como no show: el servicio de reservas lo rechazó. No se cambió nada.',
    'marked_no_show' => 'Marcada como no show. Las noches que ocupaba vuelven a estar libres en el calendario.',
];
