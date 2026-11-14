<?php

// app/Support/Booking/BookingTerms.php — the booking terms a guest is shown before paying, built from the property's real settings.

return [
    'who_title' => 'Who you are booking with',
    'who_seller' => ':property is the seller of your stay and is responsible for it.',
    'who_system' => 'Vyrobel provides the booking system and handles your details only on behalf of the property.',
    'who_contact' => 'Contact the property: :contact',
    'cancel_title' => 'Cancellation',
    'cancel_none' => 'This property has not set a free-cancellation rule. Ask it before you book.',
    'cancel_until' => 'Free cancellation until :date.',
    'cancel_days_before' => '{0} Free cancellation until the day of check-in.|{1} Free cancellation up to :count day before check-in.|[2,*] Free cancellation up to :count days before check-in.',
    'cancel_after' => 'After that date, free cancellation no longer applies. What the property charges then is set by the property, not by this page. Read its own terms if it has added any, or ask it before you book.',
    'stay_title' => 'Your stay',
    'stay_check_in' => 'Check-in from :time.',
    'stay_check_out' => 'Check-out by :time.',
    'stay_min' => '{1} Minimum stay: :count night.|[0,*] Minimum stay: :count nights.',
    'stay_max' => '{1} Maximum stay: :count night.|[0,*] Maximum stay: :count nights.',
    'stay_pets' => 'Pets: :rule.',
    'stay_smoking' => 'Smoking: :rule.',
    'stay_deposit' => 'A deposit is required when you book.',
];
