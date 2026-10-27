<?php

// app/Livewire/UnitsList.php — the operator Units page: room-type kinds, filter chips, sort wording and room-type card states.

return [
    // the room-type kinds, by code
    'kind_room' => 'Room',
    'kind_apartment' => 'Apartment',
    'kind_villa' => 'Villa',
    'kind_dorm' => 'Dorm',
    // the hint beside each room type in the Add units picker (:sleeps is a number)
    'hint' => ':kind · sleeps :sleeps',
    'hint_property' => ':kind · sleeps :sleeps · :property',
    // the filter chips
    'chip_all' => 'All',
    'chip_bookable' => 'Bookable',
    'chip_paused' => 'Paused',
    'chip_to_clean' => 'To clean',
    'chip_out_of_order' => 'Out of order',
    // how the table is sorted (read after "Units, " and after the row count)
    'sorted_unit_asc' => 'by unit, A–Z',
    'sorted_unit_desc' => 'by unit, Z–A',
    'sorted_room_type_asc' => 'by room type, A–Z',
    'sorted_room_type_desc' => 'by room type, Z–A',
    'sorted_sleeps_desc' => 'sleeps most first',
    'sorted_sleeps_asc' => 'sleeps fewest first',
    // what keeps a room type from being booked, on its card
    'state_no_units' => 'No units yet',
    'state_none_bookable' => 'No unit taking bookings',
    'state_no_prices' => 'No prices yet',
    'state_ready' => 'Units and prices set',
];
