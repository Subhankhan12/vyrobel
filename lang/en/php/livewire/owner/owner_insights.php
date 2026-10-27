<?php

// app/Livewire/Owner/OwnerInsights.php — the texts the owner Insights component builds (KPI cards, ranges, observations).

return [
    // "Worth a look" observations
    'look_hottest_title' => ':day runs hottest',
    'look_hottest_body' => ':top_day averages :top_pct% occupancy while :low_day trails at :low_pct% — worth weighing a stronger :top_day rate against a midweek nudge.',
    'look_review_rates' => 'Review your rates',
    'look_window_title' => '{1} Guests book about :count day ahead|[0,*] Guests book about :count days ahead',
    'look_window_body' => '{1} Your median booking window is :count day. Rate changes land best before that window opens for the dates you want to move.|[0,*] Your median booking window is :count days. Rate changes land best before that window opens for the dates you want to move.',
    'look_open_calendar' => 'Open your calendar',
    'look_on_books_title' => ':amount already on the books',
    'look_on_books_body' => 'Room revenue booked for the next 90 days. Open dates in that window are where a rate tweak still has time to work.',
    // KPI cards
    'card_occupancy' => 'Occupancy',
    'card_occupancy_sub' => ':sold of :available unit-nights',
    'card_avg_rate' => 'Avg nightly rate',
    'card_avg_rate_sub' => 'per night sold',
    'card_next_90' => 'Next 90 days',
    'card_next_90_sub' => 'already on the books',
    'card_median_lead' => 'Median lead time',
    // the English has always said "days", also for 1
    'card_median_lead_value' => '{1} :count days|[0,*] :count days',
    'card_median_lead_sub' => 'booking to check-in',
    'card_median_lead_empty' => 'needs booking history',
    // the derived caption under the day-of-week chart
    'busiest_night' => 'Your busiest night of the week is :day.',
    // range pills
    'range_ytd' => 'Year to date',
    'range_6m' => 'Last 6 months',
];
