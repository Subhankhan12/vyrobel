<?php

// app/Http/Controllers/Owner/OwnerRateController.php — the owner's rate write (flash messages).

return [
    'invalid_dates' => 'Choose valid start and end dates.',
    'end_before_start' => 'The end date can’t be before the start date.',
    'span_too_long' => 'Please apply at most a year at a time.',
    'invalid_rate' => 'Enter a valid nightly rate, e.g. 175 or 175.50.',
    'invalid_min_stay' => 'A minimum stay must be a whole number of nights (1–365), or blank.',
    'invalid_weekend_uplift' => 'A weekend uplift must be a percentage between 0 and 200, or blank.',
    'rate_updated' => '{1} Rate updated across :count night. It’s live now.|[0,*] Rate updated across :count nights. It’s live now.',
    'no_rate_plan' => 'That room type has no rate plan, so the rate could not be stored.',
];
