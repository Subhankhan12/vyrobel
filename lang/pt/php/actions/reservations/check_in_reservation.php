<?php

// I18N-01 — pt translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Não é possível fazer o check-in de uma reserva cancelada: a reserva está fechada e o quarto pode já ter sido revendido. Um hóspede que regressa precisa de uma nova reserva.',
    'refused_no_show' => 'Não é possível fazer o check-in de uma reserva marcada como no-show: a reserva está fechada e as suas noites voltaram a ficar disponíveis para venda. Para alojar este hóspede, crie uma «Nova reserva» na receção — volta a verificar a disponibilidade antes de reservar. O no-show e qualquer taxa já lançada na reserva ficam no registo do hóspede.',
    'refused_checked_out' => 'Não é possível fazer o check-in de uma reserva com check-out feito: a partida já foi registada e processada. Não é possível reverter um check-out.',
    'refused_tentative' => 'Não é possível fazer o check-in de uma reserva provisória: o quarto está retido, mas não confirmado, pelo que ainda ninguém prometeu a estadia a este hóspede. Prima primeiro «Confirmar retenção» nesta reserva e depois faça o check-in. As noites já estão retidas por esta reserva, por isso a confirmação não ocupa mais nada e não pode falhar por falta de disponibilidade.',
    'refused_other' => 'Não é possível fazer o check-in de uma reserva com o estado «:status».',
];
