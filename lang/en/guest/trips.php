<?php

// resources/views/guest/trips.blade.php — the guest's Trips page: travel-account hero, tabs, search and trip cards.

return [
    'page_title' => 'Your trips',
    'eyebrow' => 'Your travel account',
    'heading' => 'Welcome back, :name',
    'subheading' => 'Your stays with us — upcoming and past.',
    // under a big number shown on its own (the number is not in the text)
    'stays_completed' => '{1} stay completed|[0,*] stays completed',
    'nights_away' => '{1} night away|[0,*] nights away',
    'countries' => '{1} country|[0,*] countries',
    'tab_upcoming' => 'Upcoming',
    'tab_past' => 'Past',
    'tab_cancelled' => 'Cancelled',
    'search_placeholder' => 'Search by name, place or ref…',
    'year_label' => 'Filter by year',
    'all_years' => 'All years',
    // a trip card: ":count nights · €480.00"
    'nights_total' => '{1} :count night · :total|[0,*] :count nights · :total',
    'write_review' => 'Write a review',
    'reviewed' => 'Reviewed — thank you',
    'manage' => 'Manage',
    'view_reservation' => 'View reservation',
    // the link clears the search and year filter
    'no_match_upcoming' => 'No upcoming trips match your search. <a href=":url" style="color:var(--vy-accent-text)">Clear filters</a>.',
    'no_match_past' => 'No past trips match your search. <a href=":url" style="color:var(--vy-accent-text)">Clear filters</a>.',
    'no_match_cancelled' => 'No cancelled trips match your search. <a href=":url" style="color:var(--vy-accent-text)">Clear filters</a>.',
    'empty_upcoming' => 'No upcoming stays. When you book, they’ll appear here.',
    'empty_cancelled' => 'No cancelled bookings — all good.',
    'empty_past' => 'No past stays yet.',
];
