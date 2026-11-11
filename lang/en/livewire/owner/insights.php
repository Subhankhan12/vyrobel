<?php

// resources/views/livewire/owner/insights.blade.php — the owner Insights page (occupancy, pace, rates, lead time).

return [
    'title' => 'Occupancy & pricing insights',
    'subtitle' => 'How your units are filling, what your rates are earning, and where the headroom is.',
    'no_units_linked' => 'No units linked to your account yet.',
    // occupancy by month
    'occupancy_heading' => 'Occupancy by month',
    'occupancy_sub' => ':range · achieved and on the books',
    'legend_achieved' => 'Achieved',
    'legend_on_the_books' => 'On the books',
    // the tooltip of one month's bar: four forms (forward month or not, last-year figure or not)
    'bar_title' => ':month · :pct%',
    'bar_title_forward' => ':month · :pct% (on the books)',
    'bar_title_ly' => ':month · :pct% · last year :ly_pct%',
    'bar_title_forward_ly' => ':month · :pct% (on the books) · last year :ly_pct%',
    // the change versus last year above a bar: the arrow is shown, the word is read by a screen reader
    'delta_up' => '<span aria-hidden="true">▲</span><span class="vy-sr-only">up </span>:pts pts',
    'delta_down' => '<span aria-hidden="true">▼</span><span class="vy-sr-only">down </span>:pts pts',
    'no_occupancy' => 'No occupancy in this period yet.',
    'ly_explainer' => '▲/▼ shows the change in points versus the same month last year — real, from your own history.',
    // booking pace
    'pace_heading' => 'Booking pace · next months',
    'pace_sub' => 'Occupancy already on the books ahead.',
    'pace_on_the_books' => ':pct% on the books',
    // worth a look
    'look_heading' => 'Worth a look',
    'look_sub' => 'What the numbers suggest — decisions stay yours.',
    // rate performance
    'rate_heading' => 'Rate performance',
    'rate_sub' => 'Achieved nightly rate, before fees.',
    'rate_occupancy' => 'occupancy :pct%',
    'adr' => 'ADR',
    'no_units' => 'No units yet.',
    // when guests book
    'lead_heading' => 'When guests book',
    'lead_sub' => 'Share of bookings by lead time, all units.',
    'lead_empty' => 'Not enough booking history yet.',
    // nights that fill first
    'weekday_heading' => 'Nights that fill first',
    'weekday_sub' => 'Average occupancy by night of the week.',
    'weekday_empty' => 'No bookings in this range yet.',
    // turn insight into rate
    'cta_heading' => 'Turn insight into rate',
    'cta_body' => 'Your rates are yours to set. When a pattern here is worth acting on, open your Rates page and adjust — the change flows straight to your live availability.',
    'proposals_awaiting' => '{1} :count rate proposal awaiting your approval →|[0,*] :count rate proposals awaiting your approval →',
    'open_rates' => 'Open your Rates',
];
