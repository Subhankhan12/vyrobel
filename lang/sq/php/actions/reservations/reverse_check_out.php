<?php

// I18N-01 — sq translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Qëndrimi kthehet në statusin «Check-in kryer».',
    'pending_tasks_removed' => '{1}Do të hiqet :count detyrë pastrimi pas largimit — ende pa nisur.|[0,*]Do të hiqen :count detyra pastrimi pas largimit — ende pa nisur.',
    'started_tasks_kept' => '{1}:count detyrë pastrimi është nisur tashmë dhe nuk do të preket — anulimi i një pune që dikush e ka nisur është vendim i pastrimit, jo pjesë e zhbërjes.|[0,*]:count detyra pastrimi janë nisur tashmë dhe nuk do të preken — anulimi i një pune që dikush e ka nisur është vendim i pastrimit, jo pjesë e zhbërjes.',
    'unit_stays_dirty' => 'Njësia mbetet e shënuar «Për pastrim». Vizitori është kthyer në dhomë, ndaj statusi «Pastër» mund të mos jetë i vërtetë.',
    'notifications_stay_delivered' => 'Njoftimet e dërguara tashmë stafit dhe vizitorit mbeten të dërguara.',
    'review_unknown' => 'Nuk kemi të dhëna nëse për këtë qëndrim është dërguar një kërkesë për vlerësim, sepse check-out u bë para se ky produkt të fillonte evidentimin e tyre. Mund të jetë dërguar ose jo.',
    'review_sent_on' => 'Një kërkesë për vlerësim iu dërgua vizitorit me email më :date. Nuk mund të tërhiqet.',
    'review_sent' => 'Një kërkesë për vlerësim iu dërgua vizitorit me email. Nuk mund të tërhiqet.',
    'review_suppressed_by_operator' => 'Nuk u dërgua asnjë kërkesë për vlerësim — kjo strukturë e ka të fikur email-in e kërkesës për vlerësim.',
    'review_suppressed_by_guest' => 'Nuk u dërgua asnjë kërkesë për vlerësim — vizitori ka kërkuar të mos marrë email-e për vlerësime.',
    'review_not_sent' => 'Nuk u dërgua asnjë kërkesë për vlerësim — nuk kishte adresë kontakti ose dërgimi dështoi.',
    'refused_not_checked_out' => 'Check-out mund të zhbëhet vetëm për një qëndrim me check-out të kryer; ky ka statusin «:status».',
];
