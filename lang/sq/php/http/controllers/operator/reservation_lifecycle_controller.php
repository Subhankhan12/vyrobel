<?php

// I18N-01 — sq translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => 'Vetëm një rezervim paraprak mund të konfirmohet — ky rezervim ka statusin «:status».',
    'hold_confirmed' => 'Rezervimi paraprak u konfirmua — rezervimi tani është një qëndrim i konfirmuar.',
    'cannot_cancel_closed' => 'Ky rezervim nuk mund të anulohet — është mbyllur tashmë.',
    'reason_too_long' => 'Arsyeja e anulimit është shumë e gjatë — maksimumi 255 karaktere.',
    'cancel_rejected' => 'Ky rezervim nuk u anulua dot — shërbimi i rezervimeve e refuzoi. Asgjë nuk u ndryshua.',
    'cancelled_refunded' => 'Rezervimi u anulua dhe rimbursimi prej :amount u krye (brenda afatit të anulimit falas). Netët e rezervuara janë të lira përsëri në kalendar.',
    'cancelled' => 'Rezervimi u anulua. Netët e rezervuara janë të lira përsëri në kalendar.',
    'cannot_no_show' => 'Ky rezervim nuk mund të shënohet si mosparaqitje — vetëm një rezervim i konfirmuar mund të shënohet kështu.',
    'no_show_rejected' => 'Ky rezervim nuk u shënua dot si mosparaqitje — shërbimi i rezervimeve e refuzoi. Asgjë nuk u ndryshua.',
    'marked_no_show' => 'U shënua si mosparaqitje. Netët e rezervuara janë të lira përsëri në kalendar.',
];
