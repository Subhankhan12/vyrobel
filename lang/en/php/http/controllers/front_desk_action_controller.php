<?php

// app/Http/Controllers/FrontDeskActionController.php — front-desk check-in / check-out, their reversals, move and reschedule.
// :status is the reservation's status code, shown as it is stored.

return [
    'cannot_check_in_from' => ':reference can’t be checked in from “:status”.',
    'checked_in' => 'Checked in :reference.',
    'cannot_check_out_from' => ':reference can’t be checked out from “:status”.',
    'override_supervisor_only' => 'Only a supervisor can check out over an outstanding balance.',
    'checked_out' => 'Checked out :reference.',
    'not_checked_out' => ':reference is not checked out, so there is no checkout to reverse.',
    // followed by its own sentence about the review email
    'checked_in_again' => ':reference is checked in again.',
    'not_checked_in' => ':reference is not checked in, so there is no check-in to reverse.',
    // may be followed by its own sentence about the money
    'back_to_confirmed' => ':reference is back to confirmed.',
    'workspace_paused' => 'The workspace is paused — moves are off.',
    'room_unavailable' => 'That room isn’t available for these dates — pick another.',
    'moved_to_new_unit' => 'Moved :reference to a new unit.',
    'move_unavailable' => 'That move isn’t available — the unit or nights are already taken.',
    'moved_to_date' => 'Moved :reference to :date.',
];
