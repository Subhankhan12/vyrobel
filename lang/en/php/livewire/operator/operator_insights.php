<?php

// app/Livewire/Operator/OperatorInsights.php — the operator back office's Insights component.

return [
    // the property switcher's "every property" choice, shown in the page subtitle
    'all_properties' => 'All properties',
    // the four headline cards: label, the "vs last year" delta, the caption under the figure
    'card_occupancy' => 'Occupancy',
    'occupancy_delta' => ':delta pts',
    'occupancy_sub' => ':sold of :available room-nights',
    'card_revpar' => 'RevPAR',
    'revpar_sub' => 'revenue per available room',
    'card_adr' => 'ADR',
    'adr_sub' => 'achieved, selected range',
    'card_booking_window' => 'Booking window',
    // the median booking window; 1 printed "1 days" before, so every count reads ":count days" in English
    'window_days' => '[0,*] :count days',
    'window_sub' => 'median, booking to check-in',
    'window_sub_no_data' => 'needs booking history',
    // the by-property table's portfolio roll-up row
    'group_row' => 'Group',
    // "Worth a look" 1 — the peak night of the week. :day is the short weekday name ("Fri"); the "s" after it is part of
    // this English text (it reads "Fris run hottest"), so a translation may drop it
    'peak_title' => ':days run hottest',
    'peak_body' => ':day nights sit at :percent% across your rooms — there may be headroom for a modest premium on the strongest nights.',
    'review_rates' => 'Review rates',
    // "Worth a look" 2 — departures cluster on two days in a row. :day / :next_day are full weekday names ("Friday");
    // the "s" after :day in the title is part of this English text ("on Fridays")
    'turnover_title' => 'Turnover piles up on :days',
    'turnover_body' => ':percent% of departures land :day–:next_day, so housekeeping load follows the same curve. A midweek nudge would flatten both.',
    'balance_schedule' => 'Balance the schedule',
    // "Worth a look" 3 — revenue already booked for the next 90 days
    'on_books_title' => ':amount already on the books',
    'on_books_body' => 'Room revenue confirmed for the next 90 days. Rates for the open dates are where the upside is.',
    'open_rates_availability' => 'Open rates & availability',
    // the date-range presets
    'range_ytd' => 'Year to date',
    'range_6m' => 'Last 6 months',
    'range_summer' => 'Summer (Jun–Aug)',
    'range_q4' => 'Q4 · on the books',
    'range_full_year' => 'Full year :year',
    // the page subtitle when no property is accessible
    'no_property' => 'No property',
];
