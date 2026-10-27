<?php

// resources/views/livewire/operator/reservations-list.blade.php — the operator back office's Reservations list (/app/reservations).

return [
    'page_title' => 'Reservations',
    // the three stat tiles (this calendar month's arrivals)
    'arrivals_this_month' => 'Arrivals this month',
    'arrivals_this_month_sub' => 'checking in this calendar month · cancellations and no-shows excluded',
    'booked_value' => 'Booked value · arrivals this month',
    'booked_value_sub' => 'billed on this month\'s arrivals',
    // the same caption when the tile shows more than one currency
    'booked_value_sub_multi' => 'billed on this month\'s arrivals · shown per currency (never summed across)',
    'balances_due' => 'Balances due · arrivals this month',
    // the caption under Balances due; :count is the number of folios still owing
    'folios_owing' => '{0} nothing due|{1} :count folio still owing|[2,*] :count folios still owing',
    // the search form
    'search_placeholder' => 'Search by guest or reference…',
    'search_label' => 'Search reservations',
    'group_booking' => 'Group booking',
    'new_reservation' => 'New reservation',
    // empty state 1: no reservations at all
    'empty_heading' => 'No reservations yet',
    'empty_body' => 'Every booking — direct, walk-in or imported — lands here once it is taken.',
    // empty state 2: the search or stage filter matched nothing; :query is the typed search
    'no_match_query' => 'No reservations match “:query”',
    'no_match_view' => 'No reservations in this view',
    // the advice under it; :count is the property's total number of reservations
    'no_match_hint' => '{1} Try a guest name or a reservation reference. :count reservation in total.|[0,*] Try a guest name or a reservation reference. :count reservations in total.',
    'no_match_hint_filter' => '{1} Try a guest name or a reservation reference, or clear the filter. :count reservation in total.|[0,*] Try a guest name or a reservation reference, or clear the filter. :count reservations in total.',
    'clear_search_filters' => 'Clear search and filters',
    // table header
    'col_guest' => 'Guest',
    'col_stay' => 'Stay',
    'col_unit' => 'Unit',
    'col_source' => 'Source',
    'col_stage' => 'Stage',
    'col_balance_due' => 'Balance due',
    // a row's stay line: ":nights · :guests", each already counted ("3 nights · 2 guests")
    'stay_sub' => ':nights · :guests',
    'nights' => '{1} :count night|[0,*] :count nights',
    'guests' => '{1} :count guest|[0,*] :count guests',
    'unassigned' => 'Unassigned',
    'refunded' => 'Refunded',
    // the pager's "1–25 of 40 :label"
    'pager_label' => 'reservations',
];
