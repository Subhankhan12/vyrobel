<?php

// app/Http/Controllers/Guest/GuestManageController.php — the guest's manage-booking page (date change, add-ons, cancellation).

return [
    'your_stay' => 'Your stay',
    'stay_closed' => 'That booking has already closed, so there is nothing left to change. Its dates and folio are still here to look at.',
    'date_change_sent' => 'Date-change request sent — we’ll confirm the new dates and any price difference.',
    'extras_requested' => 'Extras requested — the property will confirm and add them to your booking.',
    'cancellation_sent' => 'Cancellation request sent — we’ll be in touch to confirm.',
    // the guest's own pending requests
    'pending_date_change' => 'Date change requested',
    'pending_add_ons' => 'Add-ons requested',
    'pending_cancellation' => 'Cancellation requested',
    // cancellation reasons (the stored value is the English code; this is what the guest reads)
    'reason_change_of_plans' => 'Change of plans',
    'reason_dates_changed' => 'My dates changed',
    'reason_found_elsewhere' => 'Found somewhere else',
    'reason_too_expensive' => 'Too expensive',
    'reason_other' => 'Other',
];
