<?php

// resources/views/pdfs/operator-report.blade.php — the operator Reports PDF (revenue · KPIs · channel mix).

return [
    'eyebrow' => 'Reports',
    'arrivals_in' => 'Arrivals in :period',
    'arrivals_count' => '{1} :count arrival|[0,*] :count arrivals',
    // a change in percentage points, e.g. "+3pp" (the sign is part of :delta)
    'pp_delta' => ':deltapp',
    'occupancy' => 'Occupancy',
    'adr' => 'ADR',
    'revpar' => 'RevPAR',
    'gross_revenue' => 'Gross revenue',
    'basis_note' => 'Occupancy · ADR · RevPAR are the stay-night basis, vs the same period last year where prior-year data exists. Revenue below is the folio (arrival) basis.',
    'revenue' => 'Revenue',
    'room' => 'Room',
    'ancillary' => 'Ancillary <span class="muted">· add-ons, products, fees</span>',
    'tax_collected' => 'Tax collected · pass-through',
    'costs_heading' => 'Costs <span class="muted" style="font-size:9px">· real data only</span>',
    'maintenance' => 'Maintenance <span class="muted">· resolved work orders</span>',
    'not_tracked' => 'not tracked yet',
    'net' => 'Net (revenue − tracked costs)',
    'revenue_by_channel' => 'Revenue by channel',
    'channel' => 'Channel',
    'bookings' => 'Bookings',
    'share' => 'Share',
    'no_channel_revenue' => 'No channel revenue in this period.',
    'top_units' => 'Top units',
    'unit' => 'Unit',
    'nights' => 'Nights',
    'room_revenue' => 'Room revenue',
    'no_unit_revenue' => 'No unit revenue in this period.',
    'revenue_by_month' => 'Revenue by month',
    'month' => 'Month',
    'footer' => 'Computed from real folio & maintenance data for :scope · :period. Occupancy/ADR/RevPAR are on the stay-night basis; revenue is on the folio (arrival) basis. Figures reconcile to the Reports screen. Vyrobel.',
];
