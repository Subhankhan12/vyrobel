<?php

// I18N-01 — sq translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Qëndrimi kthehet në statusin «Konfirmuar» dhe dhoma mbetet e rezervuar për të njëjtat netë.',
    'history_kept' => 'Vetë veprimi i check-in mbetet në historikun e rezervimit. Ai ka ndodhur dhe evidenca e vërteton këtë — zhbërja shtohet pranë tij.',
    'charges_kept' => '{1}:count debitim i shtuar që nga check-in, me vlerë totale :amount, mbetet në llogarinë e qëndrimit. Zhbërja e një check-in nuk i kthen paratë e shpenzuara — nëse ishte gabim, hiqeni edhe atë te llogaria e qëndrimit.|[0,*]:count debitime të shtuara që nga check-in, me vlerë totale :amount, mbeten në llogarinë e qëndrimit. Zhbërja e një check-in nuk i kthen paratë e shpenzuara — nëse ishin gabim, hiqini edhe ato te llogaria e qëndrimit.',
    'online_check_in_kept' => 'Check-in online i bërë nga vetë vizitori mbetet i përfunduar. Ai e ka plotësuar realisht dhe ky veprim nuk i kërkon ta plotësojë përsëri.',
    'already_reversed' => 'Ky check-in u zhbë pak më parë — qëndrimi është kthyer në statusin «Konfirmuar».',
    'refused_not_checked_in' => 'Check-in mund të zhbëhet vetëm për një qëndrim me check-in të kryer; ky ka statusin «:status».',
];
