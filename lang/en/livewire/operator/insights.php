<?php

// resources/views/livewire/operator/insights.blade.php — the operator back office's Insights page (/app/insights).

return [
    'page_title' => 'Portfolio insights',
    // the custom date-range picker beside the range presets
    'range_start_label' => 'Range start',
    'range_start_placeholder' => 'Start date',
    'range_end_label' => 'Range end',
    'range_end_placeholder' => 'End date',
    'switch_property_hint' => 'Switch property in the top bar →',
    // shown when the typed custom range can't be used; :count is the longest window allowed, in months
    'custom_rejected' => '[0,*] That date range couldn\'t be used — check the start is on or before the end, and that the window covers no more than :count months. Showing :range instead.',
    'multi_currency' => 'This scope spans more than one currency — money figures cover :range in the primary currency only.',
    // Occupancy by month: the card, its legend and the chart
    'occupancy_by_month' => 'Occupancy by month',
    'legend_this_year' => 'This year',
    'legend_on_the_books' => 'On the books',
    'legend_last_year' => 'Last year',
    // "last year" figure: a bar's tooltip, and the pace row's LY reading
    'ly_percent' => 'LY :percent%',
    'not_enough_data' => 'Not enough data in this range yet.',
    // Pace by property
    'pace_by_property' => 'Pace by property',
    'pace_intro' => 'Next 30 days booked vs the same moment last year.',
    'tone_ahead' => 'Ahead',
    'tone_behind' => 'Behind',
    'tone_on_pace' => 'On pace',
    'pace_booked' => ':percent% booked',
    'pace_booked_with_ly' => ':percent% booked · LY :ly_percent%',
    'no_forward_bookings' => 'No forward bookings yet.',
    // Worth a look (derived observations)
    'worth_a_look' => 'Worth a look',
    'worth_a_look_eyebrow' => 'What the numbers suggest — decisions stay yours.',
    'worth_a_look_empty' => 'Nothing stands out in this range yet — more booking history will surface real patterns.',
    'channel_pace_title' => 'Channel-level pace',
    'channel_pace_body' => 'Per-channel gap analysis needs a connected channel manager. Vyrobel won’t estimate a figure it can’t measure.',
    'connect_a_channel' => 'Connect a channel →',
    // By property (the rail table)
    'by_property' => 'By property',
    'col_occ' => 'Occ',
    'col_adr' => 'ADR',
    'col_revpar' => 'RevPAR',
    // Channel mix
    'channel_mix' => 'Channel mix',
    'channel_mix_sub' => 'Share of room-nights, :range.',
    'no_bookings' => 'No bookings in this range yet.',
    'channel_mix_note' => 'Real booking sources. OTA bookings show as “OTA” — the per-brand split (Booking.com/Airbnb/Expedia) isn’t modelled.',
    // Nights that fill first (day-of-week occupancy)
    'nights_fill_first' => 'Nights that fill first',
    'nights_fill_first_sub' => 'Average occupancy by night of week.',
    'no_weekly_pattern' => 'Not enough stays yet to show a weekly pattern.',
];
