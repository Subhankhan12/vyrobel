<?php

// app/Http/Controllers/Owner/OwnerBlockController.php — the owner Block Calendar: block, series and release notices.

return [
    'series_nothing_to_hold' => 'Every date in that series is already booked or blocked — nothing to hold.',
    // the English has always said "blocks" for every count; a language may add its own forms
    'series_created' => '[0,*] :count blocks created across the series. Guests can no longer book them.',
    // :count is the series' total, :created how many of them were made
    'series_partly_created' => '[0,*] :created of :count blocks created — the rest were already booked or blocked.',
    'property_removed' => 'That unit is at a property that has been removed, so its dates can no longer be blocked.',
    'dates_taken' => 'Those dates include a guest booking or an existing block — pick open dates.',
    'dates_blocked' => 'Dates blocked. Guests can no longer book them.',
    'block_removed' => 'Block removed. Those dates are open again.',
    'series_removed' => 'Series removed. Those dates are open again.',
];
