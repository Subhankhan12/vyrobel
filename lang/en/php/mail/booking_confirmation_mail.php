<?php

// app/Mail/BookingConfirmationMail.php — the guest's booking confirmation: the text the Mailable builds around the template.

return [
    // stands in for the property name when there is none
    'your_stay' => 'your stay',
    // stands in for the guest's first name in the template's greeting
    'there' => 'there',
    'check_in_from' => 'from :time',
    'check_out_by' => 'by :time',
    'accommodation_nights' => '{1} Accommodation · :count night|[0,*] Accommodation · :count nights',
    // the guests line, parts joined with " · "
    'adults' => '{1} :count adult|[0,*] :count adults',
    'children' => '{1} :count child|[0,*] :count children',
    // the source line, keyed by the booking's source code
    'booked_direct_with' => 'Booked direct with :property',
    'booked_direct_with_us' => 'Booked direct with us',
    'booked_via_partner' => 'Booked via a travel partner',
    'booked_with' => 'Booked with :property',
    'booked_with_us' => 'Booked with us',
];
