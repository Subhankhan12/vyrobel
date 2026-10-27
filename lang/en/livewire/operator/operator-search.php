<?php

// resources/views/livewire/operator/operator-search.blade.php — global search (/app/search).

return [
    'title' => 'Search',
    'subtitle' => 'Everything, in one place',
    'search_aria' => 'Search reservations, guests, units and owners',
    'search_placeholder' => 'Search reservations, guests, units, owners…',
    'clear_search' => 'Clear the search',
    'dates' => 'Dates',
    'checkin_from' => 'Check-in from',
    'from' => 'From',
    'checkin_to' => 'Check-in to',
    'to' => 'To',
    'any_status' => 'Any status',
    'source' => 'Source',
    'any_source' => 'Any source',
    'occupancy' => 'Occupancy',
    // the "no occupancy filter" chip
    'any_occupancy' => 'Any',
    'occupancy_1' => '1 guest',
    'occupancy_2' => '2 guests',
    'occupancy_3plus' => '3+ guests',
    'hint' => 'Search a guest by name or email, a reservation by reference (R-…), or a unit. Phone search isn’t available (numbers are stored encrypted).',
    'email_needs_guest_access' => 'Searching a guest by email needs guest access — you can search by <strong>name</strong> here.',
    'recent_searches' => 'Recent searches',
    'upcoming_arrivals' => 'Upcoming arrivals',
    'start_prompt' => 'Type a reference, name, unit code or owner above to search across the whole property.',
    'no_matches' => 'No matches for “:query”. Try a reference, a guest or owner name, or a unit code.',
    'results_for' => '{1} :count result for “:query”|[0,*] :count results for “:query”',
    'sort' => 'Sort',
    'sort_newest' => 'Newest',
    'sort_checkin' => 'Check-in',
    'sort_reference' => 'Reference',
    'clear_all_filters' => '× Clear all filters',
    // the result-type tabs and the group headings
    'type_reservations' => 'Reservations',
    'type_guests' => 'Guests',
    'type_units' => 'Units',
    'type_owners' => 'Owners',
    // the badge on each result row, naming where the row leads
    'guest_profile_badge' => 'Guest profile',
    'unit_badge' => 'Units',
    'owner_detail_badge' => 'Owner detail',
];
