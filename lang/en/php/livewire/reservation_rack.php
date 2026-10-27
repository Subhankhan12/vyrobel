<?php

// app/Livewire/ReservationRack.php — the operator Calendar (rack) class: the texts it builds for the view and the New reservation dialog.

return [
    // a unit row that cannot take a booking (a dateless state, shown as a band across the window)
    'state_paused' => 'Paused',
    'state_out_of_service' => 'Out of service',
    // the window shown in the toolbar; :from is "05 Oct", :to is "18 Oct 2026"
    'window_label' => ':from – :to',
    // why the New reservation dialog cannot look for rooms for the dates entered
    'check_out_after_check_in' => 'Check-out must be after check-in.',
    'check_in_in_past' => 'Check-in can’t be in the past — pick today or later.',
    'stay_too_long' => 'A booking can be at most :max nights — pick a shorter stay.',
    // a property with no unit that can take a booking; :property is its name
    'none_bookable_title' => 'None of :property’s units can take a booking',
    'none_bookable_line' => 'They’re all paused or out of service — reopen one on the Units page to book it here.',
    'no_rooms_title' => ':property has no rooms yet',
    'no_rooms_line' => 'Its rooms show here once its units are added.',
    // a saved season that was never applied; :price is the formatted amount, :from/:to like "28 Sep"
    'season_offer' => ':name (:price a night, :from → :to)',
    // the room types with unpriced nights, as a list: :names is "A, B", :last the final one
    'names_and' => ':names and :last',
    // a hatched block period on a unit's row, by its reason
    'block_out_of_order' => 'Out of order',
    'block_owner' => 'Owner block',
    'block_held' => 'Held',
    'block_blocked' => 'Blocked',
];
