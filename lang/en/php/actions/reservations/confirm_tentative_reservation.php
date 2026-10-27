<?php

// app/Actions/Reservations/ConfirmTentativeReservation.php — the refusals an operator reads when a hold cannot be confirmed.

return [
    // :status is the raw status code (cancelled / no_show), as before
    'refused_closed' => 'Cannot confirm a :status reservation: the booking is closed and its nights were released back to sale. A returning guest needs a new booking.',
    'refused_checked_in' => 'Cannot confirm a checked-in reservation: the guest has already arrived, so the booking is past confirmation.',
    'refused_checked_out' => 'Cannot confirm a checked-out reservation: the stay is already over.',
    'refused_enquiry' => 'Cannot confirm an enquiry: only a tentative hold can be confirmed.',
    'refused_other' => 'Cannot confirm a :status reservation: only a tentative hold can be confirmed.',
];
