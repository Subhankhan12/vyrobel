<?php

// app/Mail/WinbackMail.php — the guest re-engagement email.

return [
    'subject' => 'We’d love to welcome you back',
    // printed straight after "since your last stay" in the email's view (with a space before it)
    'at_property' => 'at :property',
];
