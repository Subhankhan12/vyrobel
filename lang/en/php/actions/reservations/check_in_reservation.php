<?php

// app/Actions/Reservations/CheckInReservation.php — the refusals a front desk reads when a check-in is not allowed.

return [
    'refused_cancelled' => 'Cannot check in a cancelled reservation: the booking is closed and the room may already have been resold. A returning guest needs a new booking.',
    'refused_no_show' => 'Cannot check in a no-show reservation: the booking is closed and its nights were released back to sale. To put this guest in a room, take a Walk-in on the front desk — it re-checks availability before it books. The no-show and any fee already charged on it stay on the guest record.',
    'refused_checked_out' => 'Cannot check in a checked-out reservation: the departure has already been recorded and acted on. If the check-out was a mistake, use Reverse check-out on the reservation first.',
    // ⚠️ "Confirm hold" is the shipped label of the reservation-detail button (ReservationLadderTest pins the two together)
    'refused_tentative' => 'Cannot check in a tentative booking: the room is held but not confirmed, so nobody has promised this guest the stay yet. Press Confirm hold on this reservation first, then check them in. The nights are already held by this booking, so confirming takes nothing further and cannot fail for availability.',
    // :status is the raw status code, as before
    'refused_other' => 'Cannot check in a :status reservation.',
];
