<?php

// I18N-01 — pt translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'A estadia volta ao estado Confirmada e o quarto continua reservado para as mesmas noites.',
    'history_kept' => 'O próprio check-in fica no histórico da reserva. Aconteceu, e o registo indica-o — a reversão é acrescentada ao lado.',
    'charges_kept' => '{1} :count lançamento efetuado desde o check-in, no total de :amount, mantém-se no folio. Reverter uma chegada não desfaz despesas — remova-o no folio se também tiver sido um erro.|[0,*] :count lançamentos efetuados desde o check-in, no total de :amount, mantêm-se no folio. Reverter uma chegada não desfaz despesas — remova-os no folio se também tiverem sido um erro.',
    'online_check_in_kept' => 'O check-in online feito pelo próprio hóspede mantém-se concluído. O hóspede preencheu-o realmente, e esta ação não lhe pede que o faça de novo.',
    'already_reversed' => 'Esse check-in já foi revertido há momentos — a estadia voltou ao estado Confirmada.',
    'refused_not_checked_in' => 'Só é possível reverter o check-in de uma estadia com check-in feito; esta estadia está no estado :status.',
];
