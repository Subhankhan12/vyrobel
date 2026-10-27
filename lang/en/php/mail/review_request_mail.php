<?php

// app/Mail/ReviewRequestMail.php — the guest's review request: the text the Mailable builds around the template.

return [
    // stand in for the unit / property name when there is none
    'your_unit' => 'your unit',
    'your_stay' => 'your stay',
    // the stay recap: :dates is the date range ("3–7 Oct")
    'stay_line' => '{1} :dates · :count night|[0,*] :dates · :count nights',
    // stands in for the guest's first name in the template's greeting
    'there' => 'there',
];
