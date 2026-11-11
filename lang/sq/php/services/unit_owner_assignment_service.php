<?php

// I18N-01 — sq translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':owner nuk mund të marrë njësi — ose nuk e ka pranuar ende ftesën, ose është hequr. Njësi mund të marrë vetëm një pronar aktiv.',
    'unit_already_owned' => 'Njësia «:unit» ka tashmë pronar: :owner, ndaj këtu nuk mund të caktohet për dikë tjetër. Mund të caktohet vetëm një njësi pa pronar.',
    'unit_owned_by_another' => 'një pronar tjetër',
    'date_invalid' => 'Zgjidhni një datë të vlefshme nga e cila do të caktohet njësia.',
    'date_in_past' => 'Zgjidhni sot ose një ditë më vonë. Një ditë që ka kaluar tashmë do të ndryshonte të ardhura që janë llogaritur tashmë.',
    'date_too_far' => 'Zgjidhni një ditë brenda :days ditëve të ardhshme.',
    'no_agreement' => 'Pronari :owner nuk ka kontratë që mbulon njësinë «:unit» më :date. Fillimisht krijoni për pronarin :owner një kontratë që mbulon këtë njësi nga :date, pastaj caktojeni njësinë.',
    'stay_across_date' => 'Një vizitor qëndron në njësinë «:unit» nga :from deri më :to, çka e përfshin datën :date. Zgjidhni datën :to ose një ditë më vonë, që asnjë qëndrim të mos ndahet mes jush dhe pronarit :owner.',
    'future_stays_with_another_owner' => 'Njësia «:unit» ka qëndrime nga :date e tutje që janë llogaritur tashmë për një pronar tjetër (:owners). Ato mbeten te pronari përkatës, ndaj njësia nuk mund të caktohet nga ajo ditë.',
];
