<?php

// I18N-01 — es translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Ese no es un motivo válido para forzar el check-out.',
    'override_note_required' => 'Para forzar el check-out con el motivo “Otro”, agregue una nota que explique lo que pasó.',
    'refused_confirmed' => 'No se puede hacer el check-out de una reserva confirmada: hay que hacer el check-in de una estancia antes de poder hacer su check-out. Si el huésped se alojó y se marcha antes de tiempo, haga primero el check-in y después el check-out. Si nunca llegó, marque la reserva como no show.',
    'refused_closed' => 'No se puede hacer el check-out de una reserva con el estado “:status”: la reserva está cerrada y no hubo estancia. Si al final el huésped se presentó, necesita una reserva nueva.',
    'refused_other' => 'No se puede hacer el check-out de una reserva con el estado “:status”.',
];
