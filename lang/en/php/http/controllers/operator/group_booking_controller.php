<?php

// app/Http/Controllers/Operator/GroupBookingController.php — creating a group booking (a block of rooms).

return [
    'no_property' => 'No property is configured yet.',
    'invalid_dates' => 'Choose valid stay dates for the block.',
    'name_required' => 'Give the block a name, e.g. “Okafor–Bello Wedding”.',
    'units_required' => 'Add at least one unit to the block.',
    'unit_unavailable' => 'One of the chosen units is no longer free or priced for those dates. Re-check availability.',
    'mixed_currency' => 'All units in a block must share one currency.',
    'unit_just_taken' => 'A unit in the block was just taken for those dates — nothing was created. Re-check availability.',
    'organiser_not_found' => 'That organiser could not be found.',
    'organiser_name_required' => 'Enter the organiser’s name, or pick an existing guest.',
    'organiser_email_invalid' => 'The organiser’s email address isn’t valid.',
];
