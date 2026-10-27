<?php

// app/Mail/RefundConfirmationMail.php — the guest's refund confirmation: the text the Mailable builds around the template.

return [
    // where the refund goes (no card number is stored)
    'refund_method' => 'your original payment method',
    // stands in for the property name when there is none
    'your_stay' => 'your stay',
    // stands in for the guest's first name in the template's greeting
    'there' => 'there',
];
