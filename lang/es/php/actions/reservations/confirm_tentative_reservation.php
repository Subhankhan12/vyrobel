<?php

// I18N-01 — es translation of lang/en/php/actions/reservations/confirm_tentative_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_closed' => 'No se puede confirmar una reserva con el estado “:status”: la reserva está cerrada y sus noches volvieron a ponerse a la venta. Si el huésped vuelve, necesita una reserva nueva.',
    'refused_checked_in' => 'No se puede confirmar una reserva con el check-in realizado: el huésped ya llegó, así que la reserva ya no está pendiente de confirmación.',
    'refused_checked_out' => 'No se puede confirmar una reserva con el check-out realizado: la estancia ya terminó.',
    'refused_enquiry' => 'No se puede confirmar una consulta: solo se puede confirmar un bloqueo provisional.',
    'refused_other' => 'No se puede confirmar una reserva con el estado “:status”: solo se puede confirmar un bloqueo provisional.',
];
