<?php

// I18N-01 — es translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'No se puede hacer el check-in de una reserva cancelada: la reserva está cerrada y es posible que la habitación ya se haya vuelto a vender. Un huésped que regresa necesita una reserva nueva.',
    'refused_no_show' => 'No se puede hacer el check-in de una reserva marcada como no show: la reserva está cerrada y sus noches se devolvieron a la venta. Para alojar a este huésped, cree una “Nueva reserva” en recepción: vuelve a comprobar la disponibilidad antes de reservar. El no show y cualquier cargo ya aplicado por él se conservan en la ficha del huésped.',
    'refused_checked_out' => 'No se puede hacer el check-in de una reserva con check-out realizado: la salida ya se registró y se actuó en consecuencia. No es posible deshacer un check-out.',
    'refused_tentative' => 'No se puede hacer el check-in de una reserva provisional: la habitación está bloqueada pero no confirmada, así que todavía nadie le ha prometido la estancia a este huésped. Pulse primero “Confirmar bloqueo” en esta reserva y después haga el check-in. Las noches ya están bloqueadas por esta reserva, así que confirmarla no ocupa nada más y no puede fallar por falta de disponibilidad.',
    'refused_other' => 'No se puede hacer el check-in de una reserva con el estado “:status”.',
];
