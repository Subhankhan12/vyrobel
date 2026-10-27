<?php

// app/Livewire/Operator/ReservationsList.php — the operator back office's Reservations list component.

return [
    // the page subtitle: the property's total number of reservations
    'subtitle' => '{1} :count reservation|[0,*] :count reservations',
    // the table footer; :shown is the rows on this page, :count the property's total
    'footer' => '{1} Showing :shown of :count reservation · newest arrivals first|[0,*] Showing :shown of :count reservations · newest arrivals first',
    // the search term echoed in the footer
    'footer_search' => '“:term”',
];
