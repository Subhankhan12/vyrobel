<?php

// I18N-01 — es translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'Los pagos con tarjeta pasan por Stripe (modo de prueba) y estarán disponibles cuando se activen las claves reales. Para cobrar ahora un pago en recepción, registre efectivo o una transferencia bancaria.',
    'payment_amount_invalid' => 'Introduzca un importe de pago mayor que cero.',
    'payment_recorded' => 'Pago registrado.',
    'charge_amount_invalid' => 'Introduzca un importe de cargo mayor que cero.',
    'charge_added' => 'Cargo agregado.',
    'refund_only_settled' => 'Solo se puede reembolsar un pago completado.',
    'already_fully_refunded' => 'Ese pago ya está reembolsado por completo.',
    'refund_amount_invalid' => 'Introduzca un importe de reembolso mayor que cero.',
    'refund_exceeds' => 'Se rechazó ese reembolso: de ese pago solo quedan :amount :currency por reembolsar.',
    'refund_issued' => 'Reembolso emitido.',
    'billed_to_account' => 'Facturado a :account. El saldo sigue pendiente hasta que se pague.',
    'returned_to_guest' => 'Devuelto al folio del huésped.',
    'hold_released' => 'Preautorización liberada: la tarjeta del huésped quedó libre y no se cobró nada.',
];
