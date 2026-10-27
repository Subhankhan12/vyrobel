<?php

// app/Support/Pdf/OwnerBookingSummaryDocument.php — the owner booking-summary PDF's sentences, labels and fallbacks.

return [
    'owner_fallback' => 'Owner',
    'reconcile_with_statement' => 'This booking summary lists the reservations that generated :period\'s gross room revenue of :gross. It accompanies statement #:number; deductions and your net payout are detailed there.',
    'reconcile_without_statement' => 'This booking summary lists the reservations that generated :period\'s gross room revenue of :gross. Deductions and your net payout are detailed on the statement for this period.',
    'subtitle' => '{1} :period · :owner · :count unit|[0,*] :period · :owner · :count units',
    'footer_with_vat' => ':company · VAT :vat',
    'commission_amount' => ':amount commission',
    'no_commission' => 'no commission',
    // reservations.source
    'source_direct' => 'Direct',
    'source_walk_in' => 'Walk-in',
    'source_ota' => 'OTA / channel',
    'source_corporate' => 'Corporate',
    'source_group' => 'Group',
    'source_agent' => 'Agent',
    'source_manual' => 'Manual',
];
