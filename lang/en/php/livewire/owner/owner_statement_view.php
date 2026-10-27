<?php

// app/Livewire/Owner/OwnerStatementView.php — the labels the owner Statement page builds in PHP.

return [
    // the gross line's sublabel, e.g. "12 nights · 2 units"
    'nights' => '{1} :count night|[0,*] :count nights',
    'units' => '{1} :count unit|[0,*] :count units',
    'nights_units' => ':nights · :units',
    // a statement's date span, e.g. "1 Sep – 30 Sep 2026"
    'date_range' => ':start – :end',
    // the proportional deduction sublabels; :pct is a whole percentage
    'pct_of_gross_room_revenue' => ':pct% of gross room revenue',
    'pct_of_gross_revenue' => ':pct% of gross revenue',
    // how a payout was paid
    'bank_transfer' => 'Bank transfer',
    // the payout badge when no payout exists yet
    'not_yet_finalized' => 'Not yet finalized',
    'awaiting_payout' => 'Awaiting payout',
];
