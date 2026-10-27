<?php

// app/Exceptions/Booking/ReservationInProgressException.php — R7's refusal: a checked-in stay cannot be cancelled or marked a no-show.

return [
    // :to_status is the raw status code, as before
    'message' => 'This stay is checked in — the guest is in the room, so it cannot be marked :to_status. Reverse the check-in first, then mark it :to_status — or check the guest out if the stay really happened.',
];
