<?php

// App\Support\Mail\UndeliverableRecipients — why an address can never receive mail (the admin console's refusals, via displayReason()).

return [
    'no_domain' => 'no domain',
    'reserved_domain' => 'reserved domain :domain',
    'reserved_tld' => 'reserved top-level domain .:tld',
    'suppressed' => 'on the suppression list',
];
