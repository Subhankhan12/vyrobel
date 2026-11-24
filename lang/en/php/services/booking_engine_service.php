<?php

// app/Services/BookingEngineService.php — why the public booking engine refuses a stay (shown on the checkout page and in the API's 422).

return [
    'stay_in_past' => 'Those dates have passed. Please choose a new arrival date.',
    // :adults and :children are the room's own limits (numbers)
    'too_many_guests' => 'That is more guests than this room takes (adults: up to :adults, children: up to :children). Please choose fewer guests or another room.',
    'plan_unavailable' => 'This rate is no longer offered. Please search again to see the current prices.',
];
