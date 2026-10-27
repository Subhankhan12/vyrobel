<?php

// resources/views/operator/early-check-in.blade.php — the early check-in confirmation (a warning, not a control).

return [
    'page_title' => 'Check in early?',
    'heading' => 'This check-in is early',
    'arrival_days_away' => '{1} Arrival is :count day away|[0,*] Arrival is :count days away',
    // the date stays inside the sentence so each language places it where its grammar needs it
    'booked_to_arrive' => ':reference is booked to arrive on <strong>:date</strong>. Checking in now records the guest as being in the room from today.',
    'different_booking' => 'If you meant a different booking, go back — the front desk board lists arrivals by date.',
    'wrong_booking_heading' => 'If this is the wrong booking',
    'wrong_booking_body' => 'A checked-in stay cannot be cancelled directly. If you check in the wrong row you can <strong>reverse the check-in</strong> to put it back to confirmed, and cancel from there — two deliberate steps rather than one silent one.',
    'check_in_anyway' => 'Check in anyway',
    'go_back' => 'Go back',
];
