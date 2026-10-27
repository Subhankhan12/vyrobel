<?php

// app/Livewire/Operator/PayoutRun.php — the operator's Payout run (/app/payouts/run).

return [
    // the page subtitle: ":statements_ready · :transfers_pending", each part counted on its own
    'subtitle' => ':statements_ready · :transfers_pending',
    'statements_ready' => '{1} :count statement ready|[0,*] :count statements ready',
    'transfers_pending' => '{1} :count transfer pending|[0,*] :count transfers pending',
    'held_reason' => 'Tax details pending — carried until cleared',
];
