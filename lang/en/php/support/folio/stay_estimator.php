<?php

// app/Support/Folio/StayEstimator.php — the sample-stay / checkout breakdown's line names and details.

return [
    'room' => 'Room',
    'tax_percent_included' => ':rate% · included',
    'tax_percent_added' => ':rate% · added',
    // ":guests" and ":nights" are the two counted phrases below; English printed the plural for every count
    'tax_per_person_night' => '× :guests × :nights',
    'tax_per_night' => '× :nights',
    'guests' => ':count guests',
    'nights' => ':count nights',
    'tax_flat' => 'flat',
    'fee_per_night' => 'per night',
    'fee_per_guest' => 'per guest',
    'fee_per_stay' => 'per stay',
];
