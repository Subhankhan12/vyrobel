<?php

// resources/views/operator/check-in-blocked.blade.php — check-in is refused because the stay has no room, or its room is closed (VY-13).

return [
    'page_title' => 'Check-in not possible',
    'heading' => 'This guest cannot be checked in yet',
    // one line per room that is not ready
    'no_room' => 'No room is assigned to this stay.',
    'out_of_service' => 'Room :room is out of service.',
    'what_to_do_heading' => 'What to do',
    'what_to_do' => 'Open the reservation and give the guest a room that is in service, or put the room back in service from Maintenance. Then check in again.',
    'open_reservation' => 'Open the reservation',
    'go_back' => 'Back to the front desk',
];
