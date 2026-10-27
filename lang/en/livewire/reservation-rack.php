<?php

// resources/views/livewire/reservation-rack.blade.php — the operator Calendar (units down the side, nights across, booking bars).

return [
    'page_title' => 'Calendar',
    'no_property_yet' => 'No property yet',
    // the window arrows; :count is the nights in view (7, 14 or 30)
    'previous_nights' => '[0,*] Previous :count nights',
    'next_nights' => '[0,*] Next :count nights',
    'nights_in_view' => 'Nights in view',
    // the range switcher's short form, "7n" — the letter is the abbreviation of "nights"
    'range_short' => ':nightsn',
    'filter_by_room_type' => 'Filter by room type',
    'all_room_types' => 'All room types',
    'new_reservation' => 'New reservation',
    // after the notice of a booking made from the dialog — opens that booking
    'open_it' => 'Open it',
    'go_to_units' => 'Go to Units',
    'open_other_calendar' => 'Open another property’s calendar',
    'rooms' => '{1} :count room|[0,*] :count rooms',
    'no_property_empty' => 'No property yet — add a property to see its reservation rack.',
    // above the grid when nights in view have no price; :names is the room types, :season a saved season ("Winter (€120 a night, 28 Sep → 31 Oct)")
    'no_price_note' => 'No price yet on the marked nights for :names, so they can’t be booked here.',
    'season_not_applied' => ':season is saved but not applied.',
    'set_prices_or_dialog' => '<a class="vy-rnr-link" href=":url">Set prices on the Rates page</a>, or set one for a stay from New reservation.',
    'set_prices' => '<a class="vy-rnr-link" href=":url">Set prices on the Rates page</a>.',
    'ask_rates_manager' => 'Ask someone who manages rates to set them.',
    'room_type' => 'Room type',
    'units' => '{1} :count unit|[0,*] :count units',
    // a night's cell in a room type's availability strip; :date is "Mon 5 Oct"
    'available_on' => ':count available on :date',
    'available_on_no_price' => ':count available on :date — no price yet, so it can’t be booked here',
    // the unit's sleeping capacity
    'sleeps' => 'Sleeps :count',
    // a paused / out-of-service unit's band; :state is "Paused" or "Out of service"
    'state_not_accepting' => ':state — not accepting new bookings',
    // an empty night on a unit's row; :unit is its code, :date "Mon 5 Oct"
    'book_unit_from' => 'Book :unit from :date',
    // a booking bar's tooltip: :guest is the name, :status and :source labels, :ref the booking reference
    'bar_title' => '{1} :guest — :status · :source · :count night · :ref|[0,*] :guest — :status · :source · :count nights · :ref',
    'bar_title_draggable' => '{1} :guest — :status · :source · :count night · :ref · drag to reschedule|[0,*] :guest — :status · :source · :count nights · :ref · drag to reschedule',
    'bar_title_unassigned' => '{1} :guest — :status · :source · :count night · :ref · not yet assigned to a unit|[0,*] :guest — :status · :source · :count nights · :ref · not yet assigned to a unit',
    // a booking bar's second line
    'bar_sub' => '{1} :status · :count night|[0,*] :status · :count nights',
    'unassigned' => 'Unassigned',
    // a second (third …) Unassigned row, when unassigned bookings overlap
    'overflow' => 'overflow',
    // the legend under the grid
    'legend_confirmed' => 'Confirmed',
    'legend_checked_in' => 'Checked in',
    'legend_checked_out' => 'Checked out',
    'legend_tentative' => 'Tentative / enquiry',
    'legend_no_show' => 'No-show',
    'legend_blocked' => 'Out of order / blocked',
    'legend_no_price' => 'No price yet',
    // the hint under the legend — separate sentences, shown by what the person may do
    'hint_drag' => '<strong>Drag a booking</strong> to a new date or unit to reschedule it — the stay length is kept and availability is re-checked.',
    'hint_click_night' => 'Click an empty night on a unit’s row to book it there.',
    'hint_hatched' => 'Hatched bars are real out-of-order / owner blocks; the header strip shows units available per night.',
    'hint_each_row' => 'Each row is a physical unit.',
    'hint_paused' => 'Each row is a physical unit; hatched bars are real out-of-order / owner blocks; the header strip shows units available per night. (The workspace is paused — moves are off.)',
];
