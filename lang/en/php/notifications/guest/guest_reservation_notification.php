<?php

// app/Notifications/Guest/GuestReservationNotification.php — the stay line under every reservation row in the guest's bell
// (the row's title is the email template's subject and is not here).

return [
    // stands in for the guest's first name in the template's subject
    'there' => 'there',
    // stands in for the property name when there is none
    'your_stay' => 'your stay',
    // :property · dates (· nights) — one key per night count shape, because the feed records a key, not a choice
    'context' => ':property · :check_in – :check_out',
    'context_one_night' => ':property · :check_in – :check_out · :nights night',
    'context_nights' => ':property · :check_in – :check_out · :nights nights',
];
