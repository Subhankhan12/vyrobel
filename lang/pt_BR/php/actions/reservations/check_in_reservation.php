<?php

// I18N-01 — pt_BR translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Não é possível fazer check-in de uma reserva cancelada: a reserva está encerrada e o quarto pode já ter sido revendido. Um hóspede que retorna precisa de uma nova reserva.',
    'refused_no_show' => 'Não é possível fazer check-in de uma reserva marcada como no-show: a reserva está encerrada e suas noites voltaram a ficar disponíveis para venda. Para colocar este hóspede em um quarto, faça um Walk-in na recepção — ele verifica a disponibilidade novamente antes de reservar. O no-show e qualquer taxa já cobrada por ele permanecem no cadastro do hóspede.',
    'refused_checked_out' => 'Não é possível fazer check-in de uma reserva com check-out feito: a saída já foi registrada e processada. Se o check-out foi um engano, use “Reverter check-out” na reserva primeiro.',
    'refused_tentative' => 'Não é possível fazer check-in de uma reserva provisória: o quarto está retido, mas não confirmado, então ninguém prometeu a estadia a este hóspede ainda. Primeiro, clique em “Confirmar retenção” nesta reserva e depois faça o check-in. As noites já estão retidas por esta reserva, então a confirmação não exige mais nada e não pode falhar por falta de disponibilidade.',
    'refused_other' => 'Não é possível fazer check-in de uma reserva com status “:status”.',
];
