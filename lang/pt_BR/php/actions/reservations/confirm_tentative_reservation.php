<?php

// I18N-01 — pt_BR translation of lang/en/php/actions/reservations/confirm_tentative_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_closed' => 'Não é possível confirmar uma reserva com status “:status”: a reserva está encerrada e suas noites voltaram a ficar à venda. Um hóspede que retorne precisa de uma nova reserva.',
    'refused_checked_in' => 'Não é possível confirmar uma reserva com check-in feito: o hóspede já chegou, então a reserva já passou da etapa de confirmação.',
    'refused_checked_out' => 'Não é possível confirmar uma reserva com check-out feito: a estadia já terminou.',
    'refused_enquiry' => 'Não é possível confirmar uma consulta: só uma retenção provisória pode ser confirmada.',
    'refused_other' => 'Não é possível confirmar uma reserva com status “:status”: só uma retenção provisória pode ser confirmada.',
];
