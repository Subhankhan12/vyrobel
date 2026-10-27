<?php

// App\Support\Inbox\MessageTemplate — the stand-ins merged into a message template when the guest's details are missing.

return [
    // stands in for the guest's first name, e.g. "Hi there,"
    'first_name_fallback' => 'there',
    // stands in for the unit's name when the guest has no booked unit
    'unit_fallback' => 'your unit',
];
