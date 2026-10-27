<?php

// app/Support/Pdf/OwnerStatementDocument.php — the owner statement PDF's status, fallbacks and line details.

return [
    'owner_fallback' => 'Owner',
    'status_paid' => 'Paid',
    'status_payout_pending' => 'Payout pending',
    // the gross line's detail: ":nights · :units" (each already counted below)
    'gross_detail' => ':nights · :units',
    'nights' => '{1} :count night|[0,*] :count nights',
    'units' => '{1} :count unit|[0,*] :count units',
    'pct_of_gross_room_revenue' => ':pct% of gross room revenue',
    'pct_of_gross_revenue' => ':pct% of gross revenue',
];
