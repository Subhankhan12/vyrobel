<?php

// app/Livewire/Owner/OwnerBlockCalendar.php — the labels the owner Block calendar builds in PHP.

return [
    // the month grid's counts, e.g. "2 held · 5 booked · 20 open"
    'stat_line' => ':held held · :booked booked · :open open',
    // a scheduled block's first and last night, e.g. "3 Oct – 5 Oct 2026"
    'date_range' => ':start – :end',
    // shown for a block saved with no reason
    'owner_hold' => 'Owner hold',
];
