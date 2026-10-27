<?php

// App\Support\Owner\OwnerBookingsReader — the owner's Bookings: where a booking came from, and its status badge (page and CSV).

return [
    'source_direct' => 'Direct',
    'source_walk_in' => 'Walk-in',
    // a booking from an online travel agency (no brand is modelled)
    'source_ota' => 'OTA',
    'source_corporate' => 'Corporate',
    'source_group' => 'Group',
    'source_agent' => 'Agent',
    'source_manual' => 'Manual',
    'status_confirmed' => 'Confirmed',
    // a tentative booking, shown to the owner as "Pending"
    'status_tentative' => 'Pending',
    'status_enquiry' => 'Enquiry',
    'status_checked_in' => 'In-house',
    'status_checked_out' => 'Completed',
    'status_cancelled' => 'Cancelled',
    'status_no_show' => 'No-show',
];
