<?php

// app/Livewire/Operator/OperatorUnitRequests.php — the Unit requests page's chips, property types and request summary.

return [
    // the filter chips, by view code
    'chip_pending' => 'Waiting',
    'chip_approved' => 'Approved',
    'chip_declined' => 'Declined',
    'chip_withdrawn' => 'Withdrawn',
    // the one-line summary of what the owner filed
    'bedrooms' => ':count bd',
    'bathrooms' => ':count ba',
    'sleeps' => 'sleeps :count',
    'rate_per_night' => ':amount/night',
    'amenities' => '{1} :count amenity|[0,*] :count amenities',
    'photos' => '{1} :count photo|[0,*] :count photos',
];
