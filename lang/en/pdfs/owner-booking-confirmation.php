<?php

// resources/views/pdfs/owner-booking-confirmation.blade.php — the owner booking confirmation PDF.

return [
    'title' => 'Booking confirmation',
    'subtitle' => '{1} :guest · :units · :count night|[0,*] :guest · :units · :count nights',
    'reservation' => 'Reservation',
    'check_in' => 'Check-in',
    'check_out' => 'Check-out',
    'nights' => 'Nights',
    'guests' => 'Guests',
    'guest' => 'Guest',
    'unit' => 'Unit',
    'source' => 'Source',
    'booked_on' => 'Booked on',
    'earnings_heading' => 'Your earnings — gross → net',
    'gross_booking_value' => 'Gross booking value',
    'net_to_you' => 'Net to you',
    'not_attributed' => 'Not yet attributed — your earnings appear once this booking is attributed to your ledger, usually after check-out.',
    'prepared_for' => 'Prepared for the property owner on :date. Figures are computed from your owner ledger and reconcile to your statement.',
];
