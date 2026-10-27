<?php

// app/Actions/Reservations/CheckOutReservation.php — the refusals a front desk reads when a checkout is not allowed.

return [
    'override_reason_unknown' => 'That is not a recognised checkout-override reason.',
    'override_note_required' => 'An "other" override needs a note saying what happened.',
    'refused_confirmed' => 'Cannot check out a confirmed reservation: a stay must be checked in before it can be checked out. If the guest stayed and is leaving early, check them in first and then out. If they never arrived, mark the booking a no-show instead.',
    // :status is the raw status code (cancelled / no_show), as before
    'refused_closed' => 'Cannot check out a :status reservation: the booking is closed and no stay took place. A guest who turned up after all needs a new booking.',
    'refused_other' => 'Cannot check out a :status reservation.',
];
