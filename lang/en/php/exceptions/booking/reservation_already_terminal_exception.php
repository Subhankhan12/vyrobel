<?php

// app/Exceptions/Booking/ReservationAlreadyTerminalException.php — the termination bound's refusal (#29), surfaced as a flash.

return [
    // :from_status / :to_status are the raw status codes, as before
    'message' => 'This reservation is already :from_status and cannot be marked :to_status.',
];
