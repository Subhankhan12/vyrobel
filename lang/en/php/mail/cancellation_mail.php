<?php

// app/Mail/CancellationMail.php — the guest's cancellation email: the text the Mailable builds around the template.

return [
    // stands in for the property name when there is none
    'your_stay' => 'your stay',
    // stands in for the guest's first name in the template's greeting
    'there' => 'there',
    // where a refund goes (no card number is stored)
    'refund_method' => 'your original payment method',
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
