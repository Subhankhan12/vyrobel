<?php

// app/Livewire/HousekeepingBoard.php — the operator's Housekeeping board component.

return [
    // the forward step offered on a room, by its current stage
    'next_start' => 'Start',
    'next_mark_clean' => 'Mark clean',
    'next_inspect' => 'Inspect',
    'urgency_guest_waiting' => 'Guest waiting',
    'urgency_guest_arriving_today' => 'Guest arriving today',
    'day_tomorrow' => 'Tomorrow',
    // :date is the day the cleaning was due, e.g. "Overdue · 30 Jul"
    'day_overdue' => 'Overdue · :date',
];
