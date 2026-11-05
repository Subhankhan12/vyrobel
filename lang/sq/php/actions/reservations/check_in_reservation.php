<?php

// I18N-01 — sq translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Nuk mund të bëhet check-in për një rezervim të anuluar: rezervimi është mbyllur dhe dhoma mund të jetë shitur tashmë përsëri. Një vizitor që kthehet ka nevojë për një rezervim të ri.',
    'refused_no_show' => 'Nuk mund të bëhet check-in për një rezervim me mosparaqitje: rezervimi është mbyllur dhe netët e tij u kthyen në shitje. Për ta vendosur këtë vizitor në një dhomë, krijoni një «Rezervim i ri» në recepsion — ai kontrollon përsëri disponueshmërinë para se të rezervojë. Mosparaqitja dhe çdo tarifë e debituar tashmë për të mbeten në profilin e vizitorit.',
    'refused_checked_out' => 'Nuk mund të bëhet check-in për një rezervim me check-out të kryer: largimi është evidentuar dhe përpunuar tashmë. Zhbërja e check-out nuk është e disponueshme.',
    'refused_tentative' => 'Nuk mund të bëhet check-in për një rezervim paraprak: dhoma është e rezervuar paraprakisht, por jo e konfirmuar, ndaj askush nuk ia ka premtuar ende këtij vizitori qëndrimin. Shtypni më parë butonin «Konfirmo rezervimin paraprak» te ky rezervim, pastaj bëni check-in. Netët janë të rezervuara tashmë nga ky rezervim, ndaj konfirmimi nuk merr asgjë tjetër dhe nuk mund të dështojë për shkak të disponueshmërisë.',
    'refused_other' => 'Nuk mund të bëhet check-in për këtë rezervim (statusi: «:status»).',
];
