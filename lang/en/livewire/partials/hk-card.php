<?php

// resources/views/livewire/partials/hk-card.blade.php — one housekeeping room card on the board.

return [
    'turnover' => 'Turnover',
    'vip' => 'VIP',
    'assign_button' => '+ Assign',
    'unassigned' => 'Unassigned',
    'elapsed' => 'Elapsed',
    // minutes spent / minutes expected, e.g. "12m / 30m"
    'elapsed_of_expected' => ':minutesm / :expectedm',
    'guest_ready' => 'Guest-ready',
    // :time is the clock time the cleaning started (e.g. 09:12)
    'started_at' => 'started :time',
];
