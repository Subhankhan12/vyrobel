<?php

// resources/views/pdfs/owner-booking-summary.blade.php — the owner booking summary PDF.

return [
    'title' => 'Booking summary',
    'bookings' => 'Bookings',
    'nights' => 'Nights',
    'occupancy' => 'Occupancy',
    'gross_revenue' => 'Gross revenue',
    'not_attributed' => 'This statement\'s gross room revenue is not attributed to any booking in this period.',
    'by_unit' => 'By unit',
    'unit' => 'Unit',
    'revenue' => 'Revenue',
    'by_channel' => 'By channel',
    'channel_bookings' => '{1} :count booking · :commission|[0,*] :count bookings · :commission',
    'bookings_this_period' => 'Bookings this period',
    'ref' => 'Ref',
    'guest' => 'Guest',
    'dates' => 'Dates',
    'reversed_note' => '{1} :count further booking claimed by this statement was fully reversed (for example, cancelled), so it contributed nothing to the gross above and is not listed.|[0,*] :count further bookings claimed by this statement were fully reversed (for example, cancelled), so they contributed nothing to the gross above and are not listed.',
    'prior_note' => '{1} :count booking claimed by this statement has room revenue dated before this period. That revenue is reported on the statement within “Prior-period adjustments”, not in the gross above.|[0,*] :count bookings claimed by this statement have room revenue dated before this period. That revenue is reported on the statement within “Prior-period adjustments”, not in the gross above.',
    'footer_right' => 'Booking summary · :period',
];
