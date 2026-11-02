<?php

// I18N-01 — pt_BR translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'A estadia volta para confirmada, e o quarto continua reservado para as mesmas noites.',
    'history_kept' => 'O próprio check-in continua no histórico da reserva. Ele aconteceu, e o registro mostra isso — a reversão é registrada junto com ele.',
    'charges_kept' => '{1} :count lançamento feito desde o check-in, no total de :amount, continua no fólio. Reverter uma chegada não desfaz gastos — remova-o no fólio se ele também foi um engano.|[0,*] :count lançamentos feitos desde o check-in, no total de :amount, continuam no fólio. Reverter uma chegada não desfaz gastos — remova-os no fólio se eles também foram um engano.',
    'online_check_in_kept' => 'O check-in on-line feito pelo próprio hóspede continua concluído. O hóspede realmente o preencheu, e esta ação não pede que ele o faça de novo.',
    'already_reversed' => 'Esse check-in já foi revertido há pouco — a estadia voltou para confirmada.',
    'refused_not_checked_in' => 'Só uma estadia com check-in feito pode ter o check-in revertido; o status desta é “:status”.',
];
