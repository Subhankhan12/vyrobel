<?php

// I18N-01 — pt_BR translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Esse não é um motivo reconhecido para a exceção de check-out.',
    'override_note_required' => 'Uma exceção com o motivo “Outro” precisa de uma observação explicando o que aconteceu.',
    'refused_confirmed' => 'Não é possível fazer check-out de uma reserva confirmada: é preciso fazer o check-in da estadia antes do check-out. Se o hóspede se hospedou e está saindo antes, faça primeiro o check-in e depois o check-out. Se ele nunca chegou, marque a reserva como no-show.',
    'refused_closed' => 'Não é possível fazer check-out de uma reserva com status “:status”: a reserva está encerrada e nenhuma estadia ocorreu. Um hóspede que acabou aparecendo precisa de uma nova reserva.',
    'refused_other' => 'Não é possível fazer check-out de uma reserva com status “:status”.',
];
