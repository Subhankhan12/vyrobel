<?php

// app/Http/Controllers/Operator/UnitRateActionController.php — the Rates page's per-unit price preview/apply: refusals and summaries.

return [
    'nothing_to_clear' => 'None of those units have their own price in that range — there is nothing to clear.',
    'choose_unit_to_price' => 'Choose at least one unit to price.',
    'preview_first' => 'Preview the change before applying it.',
    'read_only' => 'Your workspace is read-only right now — rate changes are paused.',
    'invalid_dates' => 'Choose valid start and end dates.',
    'end_before_start' => 'The end date can’t be before the start date.',
    'range_too_long' => 'That date range is too long — please apply at most a year at a time.',
    'choose_mode' => 'Choose whether to set a price or clear the per-unit prices.',
    'invalid_rate' => 'Enter a valid nightly rate, e.g. 175 or 175.50.',
    'choose_room_type' => 'Choose a room type.',
    'no_rate_plan' => 'That room type has no rate plan, so there is nowhere to store a price.',
    'choose_unit' => 'Choose at least one unit.',
    'too_many_units' => 'That is too many units for one operation.',
    'cleared_summary' => '{1} Cleared the per-unit price for :count unit — it now follows the room-type rate again.|[0,*] Cleared the per-unit price for :count units — they now follow the room-type rate again.',
    // :units and :nights are the two counted phrases below
    'priced_summary' => 'Priced :units individually for :nights.',
    'units_count' => '{1} :count unit|[0,*] :count units',
    'nights_count' => '{1} :count night|[0,*] :count nights',
];
