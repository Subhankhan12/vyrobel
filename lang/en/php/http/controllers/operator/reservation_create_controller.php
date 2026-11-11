<?php

// app/Http/Controllers/Operator/ReservationCreateController.php — the New reservation wizard's booking write.

return [
    'workspace_paused' => 'Your workspace is paused (read-only) — new bookings are off until you resume. Resume from Billing.',
    'invalid_dates' => 'Choose valid check-in and check-out dates.',
    'too_many_nights' => 'A booking can be at most :max nights — pick a shorter stay.',
    'choose_room_and_unit' => 'Choose a room and a specific unit for the stay.',
    'room_type_unavailable' => 'That room type isn’t available.',
    'room_not_free_or_unpriced' => 'That room isn’t free, or has no price for every night of these dates. Pick a unit from the list again.',
    'unit_no_longer_free' => 'That unit is no longer free for these dates — pick another one.',
    'card_coming_soon' => 'Card payments are coming soon — take cash or bank transfer now, or record it later from the folio.',
    'no_payment_permission' => 'You don’t have permission to record a payment.',
    'invalid_payment_amount' => 'Enter a valid payment amount, e.g. 150 or 150.00.',
    'choose_override_reason' => 'Choose a reason from the list to book anyway.',
    'override_note_required' => 'Say why in the note — it’s needed when you choose Other.',
    'dates_restricted' => 'This booking wasn’t made — these dates are restricted. Book anyway with a reason below, or change the dates in step 1.',
    'unit_just_taken' => 'That unit was just taken for these dates — nothing was booked. Pick another one.',
    'reservation_created' => 'Reservation :reference created.',
    'credit_applied' => ':amount credit applied.',
    'recorded_as_paid' => ':amount recorded as paid.',
    'walk_in_checked_in' => 'Walk-in :reference checked in.',
    'walk_in_created' => 'Walk-in :reference created.',
    'guest_not_found' => 'That guest could not be found — search for them again.',
    'choose_or_name_guest' => 'Choose an existing guest, or enter a name for a new one.',
    'invalid_guest_email' => 'That guest email address isn’t valid.',
    // DEC-r (2026-11-11): the email already belongs to a guest (the page was without its offer, or it was stale)
    'guest_email_exists' => 'A guest with this email already exists (:name). Type the email again and choose “Use this guest”, or “Create a new one”.',
];
