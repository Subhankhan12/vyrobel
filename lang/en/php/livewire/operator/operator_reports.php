<?php

// app/Livewire/Operator/OperatorReports.php — the operator back office's Reports component.

return [
    // the property switcher's "every property" choice, shown in the page subtitle
    'all_properties' => 'All properties',
    // the page subtitle when no property is accessible
    'no_property' => 'No property',
    // the four headline cards: label and the caption under the figure
    'card_occupancy' => 'Occupancy',
    'occupancy_sub' => 'room-nights sold',
    // the occupancy "vs last year" delta in percentage points; :delta is the signed number ("+3")
    'occupancy_delta' => ':deltapp',
    'card_adr' => 'ADR',
    'adr_sub' => 'average daily rate',
    'card_revpar' => 'RevPAR',
    'revpar_sub' => 'per available room',
    'card_gross' => 'Gross revenue',
    'gross_sub' => 'room + ancillary · folio basis',
];
