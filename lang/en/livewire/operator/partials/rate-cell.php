<?php

// resources/views/livewire/operator/partials/rate-cell.blade.php — one rate-grid cell: what is left and the stay rules.

return [
    'closed' => 'Closed',
    'full' => 'Full',
    // the units still free that night (one form in English; a language with plural forms adds them)
    'left' => ':count left',
    // the English always said "nights", even for 1
    'min_stay_title' => 'Minimum stay: :count nights',
    'min_stay_short' => 'Min :nights',
    'max_stay_title' => 'Maximum stay: :count nights',
    'max_stay_short' => 'Max :nights',
    'closed_to_arrival_title' => 'Closed to arrival — a stay cannot start this night',
    'closed_to_arrival_short' => 'No arr.',
    'closed_to_departure_title' => 'Closed to departure — a stay cannot end this night',
    'closed_to_departure_short' => 'No dep.',
    'booked_pct' => ':pct% booked',
];
