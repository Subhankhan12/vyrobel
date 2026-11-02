<?php

// I18N-01 — pt translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Este não é um motivo reconhecido para forçar o check-out.',
    'override_note_required' => 'O motivo «Outro» exige uma nota a explicar o que aconteceu.',
    'refused_confirmed' => 'Não é possível fazer o check-out de uma reserva confirmada: uma estadia tem de ter check-in antes de poder ter check-out. Se o hóspede ficou e está a sair mais cedo, faça primeiro o check-in e depois o check-out. Se nunca chegou, marque a reserva como no-show.',
    'refused_closed' => 'Não é possível fazer o check-out de uma reserva com o estado «:status»: a reserva está fechada e não houve estadia. Um hóspede que acabou por aparecer precisa de uma nova reserva.',
    'refused_other' => 'Não é possível fazer o check-out de uma reserva com o estado «:status».',
];
